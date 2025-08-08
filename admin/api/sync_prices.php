<?php
/*
 * 파일명: sync_prices.php
 * 위치: /admin/api/sync_prices.php
 * 기능: 나노메모리 데이터 동기화 API - nm_products에서 purchase_prices로 데이터 동기화
 * 작성일: 2025-01-31
 * 수정일: 2025-08-01
 */

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 세션 시작
session_start();

// 인증 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => 'Unauthorized', 'message' => '로그인이 필요합니다.']));
}

require_once(__DIR__ . '/../../db_config.php');

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

try {
    $pdo = getDB();
    
    // 현재 시간
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $currentTime = $now->format('Y-m-d H:i:s');
    
    // 1. nm_products 테이블 존재 확인
    $tableCheckSql = "SHOW TABLES LIKE 'nm_products'";
    $tableCheck = $pdo->query($tableCheckSql)->fetch();
    
    if (!$tableCheck) {
        throw new Exception('nm_products 테이블이 존재하지 않습니다. 먼저 나노메모리 파서를 실행해주세요.');
    }
    
    // 2. nm_products 데이터 확인
    $nmCountSql = "SELECT COUNT(*) FROM nm_products";
    $nmCount = $pdo->query($nmCountSql)->fetchColumn();
    
    if ($nmCount == 0) {
        throw new Exception('nm_products 테이블에 제품이 없습니다. 먼저 나노메모리 파서를 실행해주세요.');
    }
    
    // 3. purchase_prices 테이블 삭제 및 재생성 (구조가 잘못된 경우를 위해)
    try {
        // 기존 테이블 삭제
        $pdo->exec("DROP TABLE IF EXISTS purchase_prices");
        
        // 올바른 구조로 테이블 생성
        $createTableSql = "
            CREATE TABLE purchase_prices (
                id INT(11) NOT NULL AUTO_INCREMENT,
                nm_product_id INT(11) NOT NULL COMMENT '나노메모리 제품 ID',
                adjustment_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage' COMMENT '조정 방식',
                adjustment_value DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '조정값',
                final_price INT(11) NOT NULL COMMENT '최종 매입가',
                is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                is_custom TINYINT(1) NOT NULL DEFAULT 0 COMMENT '개별 수정 여부',
                custom_note TEXT COMMENT '수정 사유',
                created_at DATETIME NOT NULL COMMENT '생성일시',
                updated_at DATETIME NOT NULL COMMENT '수정일시',
                PRIMARY KEY (id),
                UNIQUE KEY uk_nm_product_id (nm_product_id),
                KEY idx_active (is_active),
                KEY idx_custom (is_custom),
                CONSTRAINT fk_nm_product FOREIGN KEY (nm_product_id) 
                    REFERENCES nm_products(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='매입 가격 관리'
        ";
        
        $pdo->exec($createTableSql);
        
    } catch (PDOException $e) {
        // 테이블이 이미 존재하는 경우 구조 확인 및 수정
        error_log('Table creation error (may already exist): ' . $e->getMessage());
        
        // category_main 컬럼이 있다면 제거
        try {
            $pdo->exec("ALTER TABLE purchase_prices DROP COLUMN category_main");
            error_log('Removed category_main column from purchase_prices table');
        } catch (PDOException $e) {
            // 컬럼이 없으면 무시
        }
    }
    
    // 4. 트랜잭션 시작
    $pdo->beginTransaction();
    
    try {
        // 5. nm_products의 모든 제품을 purchase_prices에 추가 (없는 것만)
        $insertSql = "
            INSERT IGNORE INTO purchase_prices 
            (nm_product_id, adjustment_type, adjustment_value, final_price, is_active, is_custom, created_at, updated_at)
            SELECT 
                nm.id,
                'percentage' as adjustment_type,
                0 as adjustment_value,
                nm.price as final_price,
                1 as is_active,
                0 as is_custom,
                :created_at,
                :updated_at
            FROM nm_products nm
            WHERE NOT EXISTS (
                SELECT 1 FROM purchase_prices pp WHERE pp.nm_product_id = nm.id
            )
        ";
        
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            ':created_at' => $currentTime,
            ':updated_at' => $currentTime
        ]);
        
        $newCount = $stmt->rowCount();
        
        // 6. 개별 수정되지 않은 제품의 가격 업데이트 (원본 가격이 변경된 경우)
        $updateSql = "
            UPDATE purchase_prices pp
            INNER JOIN nm_products nm ON pp.nm_product_id = nm.id
            SET 
                pp.final_price = CASE 
                    WHEN pp.adjustment_type = 'percentage' THEN 
                        ROUND(nm.price * (1 + pp.adjustment_value / 100))
                    ELSE 
                        nm.price + pp.adjustment_value
                END,
                pp.updated_at = :updated_at
            WHERE pp.is_custom = 0
        ";
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([':updated_at' => $currentTime]);
        
        $updatedCount = $stmt->rowCount();
        
        // 7. 통계 정보 수집
        $totalNmProducts = $pdo->query("SELECT COUNT(*) FROM nm_products")->fetchColumn();
        $totalPpProducts = $pdo->query("SELECT COUNT(*) FROM purchase_prices")->fetchColumn();
        $customProducts = $pdo->query("SELECT COUNT(*) FROM purchase_prices WHERE is_custom = 1")->fetchColumn();
        
        // 트랜잭션 커밋
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'new' => $newCount,
            'updated' => $updatedCount,
            'stats' => [
                'nm_products_total' => $totalNmProducts,
                'purchase_prices_total' => $totalPpProducts,
                'custom_products' => $customProducts
            ],
            'message' => "동기화 완료: 신규 {$newCount}개, 업데이트 {$updatedCount}개\n전체 나노메모리 제품: {$totalNmProducts}개\n관리중인 제품: {$totalPpProducts}개"
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Sync Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Sync Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>