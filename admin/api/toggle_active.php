<?php
/*
 * 파일명: toggle_active.php
 * 위치: /admin/api/toggle_active.php
 * 기능: 제품 활성화 상태 토글 API
 * 작성일: 2025-01-31
 */

// 인증 확인
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $is_active = intval($input['is_active'] ?? 0);
    
    if (!$id) {
        exit(json_encode(['error' => 'ID가 필요합니다.']));
    }
    
    $pdo = getDB();
    
    // 현재 시간
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $currentTime = $now->format('Y-m-d H:i:s');
    
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
        $stmt->execute([
            ':id' => $id,
            ':active' => $is_active,
            ':updated_at' => $currentTime
        ]);
    } else {
        // 원본 가격 가져오기
        $priceSql = "SELECT price FROM nm_products WHERE id = :id";
        $stmt = $pdo->prepare($priceSql);
        $stmt->execute([':id' => $id]);
        $price = $stmt->fetchColumn();
        
        if ($price !== false) {
            // 삽입
            $sql = "INSERT INTO purchase_prices 
                    (nm_product_id, adjustment_type, adjustment_value, final_price, is_active, created_at, updated_at)
                    VALUES (:id, 'percentage', 0, :price, :active, :created_at, :updated_at)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':price' => $price,
                ':active' => $is_active,
                ':created_at' => $currentTime,
                ':updated_at' => $currentTime
            ]);
        } else {
            exit(json_encode(['error' => '제품을 찾을 수 없습니다.']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => '상태가 변경되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Toggle Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()]);
}