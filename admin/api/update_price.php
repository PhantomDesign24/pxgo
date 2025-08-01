<?php
/*
 * 파일명: update_price.php
 * 위치: /admin/api/update_price.php
 * 기능: 개별 제품 가격 수정 API
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $adjustment_type = $input['adjustment_type'] ?? 'percentage';
    $adjustment_value = floatval($input['adjustment_value'] ?? 0);
    $final_price = intval($input['final_price'] ?? 0);
    $custom_note = $input['custom_note'] ?? '';
    $is_active = intval($input['is_active'] ?? 1);
    
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
                SET adjustment_type = :type,
                    adjustment_value = :value,
                    final_price = :final_price,
                    is_custom = 1,
                    custom_note = :note,
                    is_active = :active,
                    updated_at = :updated_at
                WHERE nm_product_id = :id";
    } else {
        // 삽입
        $sql = "INSERT INTO purchase_prices 
                (nm_product_id, adjustment_type, adjustment_value, final_price, is_custom, custom_note, is_active, created_at, updated_at)
                VALUES (:id, :type, :value, :final_price, 1, :note, :active, :created_at, :updated_at)";
    }
    
    $stmt = $pdo->prepare($sql);
    $params = [
        ':id' => $id,
        ':type' => $adjustment_type,
        ':value' => $adjustment_value,
        ':final_price' => $final_price,
        ':note' => $custom_note,
        ':active' => $is_active,
        ':updated_at' => $currentTime
    ];
    
    if (!$exists) {
        $params[':created_at'] = $currentTime;
    }
    
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => '가격이 수정되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Update Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '처리 중 오류가 발생했습니다.']);
}