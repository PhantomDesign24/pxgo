<?php
/*
 * 파일명: sync_prices.php
 * 위치: /admin/api/sync_prices.php
 * 기능: 나노메모리 데이터 동기화 API
 * 작성일: 2025-01-31
 */

// 인증 확인
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
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
    
    // 1. purchase_prices 테이블 생성 (없는 경우)
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
    
    // 2. 나노메모리 제품 중 purchase_prices에 없는 제품 추가
    $insertSql = "
        INSERT INTO purchase_prices (nm_product_id, adjustment_type, adjustment_value, final_price, is_active, created_at, updated_at)
        SELECT 
            nm.id,
            'percentage',
            0,
            nm.price,
            1,
            :created_at,
            :updated_at
        FROM nm_products nm
        LEFT JOIN purchase_prices pp ON nm.id = pp.nm_product_id
        WHERE pp.id IS NULL
    ";
    
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        ':created_at' => $currentTime,
        ':updated_at' => $currentTime
    ]);
    
    $newCount = $stmt->rowCount();
    
    // 3. 개별 수정되지 않은 제품의 원본 가격 업데이트
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
    
    echo json_encode([
        'success' => true,
        'new' => $newCount,
        'updated' => $updatedCount,
        'message' => "동기화 완료: 신규 {$newCount}개, 업데이트 {$updatedCount}개"
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Sync Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '동기화 중 오류가 발생했습니다: ' . $e->getMessage()]);
}