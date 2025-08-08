<?php
/*
 * 파일명: toggle_active.php
 * 위치: /admin/api/toggle_active.php
 * 기능: 제품 활성화 상태 토글 API
 * 작성일: 2025-01-31
 * 수정일: 2025-08-01
 */

// 오류 표시 설정 (개발 환경에서만 사용)
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $is_active = isset($input['is_active']) ? intval($input['is_active']) : 0;
    
    if (!$id) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'ID가 필요합니다.'
        ]));
    }
    
    $pdo = getDB();
    
    // 현재 시간
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $currentTime = $now->format('Y-m-d H:i:s');
    
    // purchase_prices 테이블이 존재하는지 확인
    $tableCheckSql = "SHOW TABLES LIKE 'purchase_prices'";
    $tableCheck = $pdo->query($tableCheckSql)->fetch();
    
    if (!$tableCheck) {
        // purchase_prices 테이블 생성
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS purchase_prices (
                id INT(11) NOT NULL AUTO_INCREMENT,
                nm_product_id INT(11) NOT NULL COMMENT '나노메모리 제품 ID',
                adjustment_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage' COMMENT '조정 방식',
                adjustment_value DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '조정값',
                final_price INT(11) NOT NULL COMMENT '최종 매입가',
                is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                is_custom TINYINT(1) NOT NULL DEFAULT 0 COMMENT '개별 수정 여부',
                custom_note TEXT COMMENT '수정 사유',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uk_nm_product_id (nm_product_id),
                KEY idx_active (is_active),
                KEY idx_custom (is_custom)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='매입 가격 관리'
        ";
        $pdo->exec($createTableSql);
    }
    
    // nm_products에 해당 제품이 있는지 먼저 확인
    $productCheckSql = "SELECT id, price FROM nm_products WHERE id = :id";
    $stmt = $pdo->prepare($productCheckSql);
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        exit(json_encode([
            'success' => false,
            'error' => '제품을 찾을 수 없습니다.'
        ]));
    }
    
    // purchase_prices 레코드 확인
    $checkSql = "SELECT id FROM purchase_prices WHERE nm_product_id = :id";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':id' => $id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // 업데이트
        $sql = "UPDATE purchase_prices 
                SET is_active = :active,
                    updated_at = :updated_at
                WHERE nm_product_id = :id";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':id' => $id,
            ':active' => $is_active,
            ':updated_at' => $currentTime
        ]);
    } else {
        // 삽입
        $sql = "INSERT INTO purchase_prices 
                (nm_product_id, adjustment_type, adjustment_value, final_price, is_active, is_custom, created_at, updated_at)
                VALUES (:id, 'percentage', 0, :price, :active, 0, :created_at, :updated_at)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':id' => $id,
            ':price' => $product['price'],
            ':active' => $is_active,
            ':created_at' => $currentTime,
            ':updated_at' => $currentTime
        ]);
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '상태가 변경되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('데이터베이스 업데이트 실패');
    }
    
} catch (PDOException $e) {
    error_log('Toggle Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Toggle Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '처리 중 오류가 발생했습니다.',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>