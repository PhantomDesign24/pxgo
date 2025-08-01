<?php
/*
 * 파일명: get_price.php
 * 위치: /admin/api/get_price.php
 * 기능: 개별 제품 가격 조회 API
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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    http_response_code(400);
    exit(json_encode(['error' => 'ID가 필요합니다.']));
}

try {
    $pdo = getDB();
    
    $sql = "SELECT 
                nm.id,
                nm.category_main,
                nm.category_sub,
                nm.classification,
                nm.product_name,
                nm.price as original_price,
                COALESCE(pp.adjustment_type, 'percentage') as adjustment_type,
                COALESCE(pp.adjustment_value, 0) as adjustment_value,
                COALESCE(pp.final_price, nm.price) as final_price,
                COALESCE(pp.is_active, 1) as is_active,
                pp.is_custom,
                pp.custom_note
            FROM nm_products nm
            LEFT JOIN purchase_prices pp ON nm.id = pp.nm_product_id
            WHERE nm.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode([
            'success' => true,
            'product' => $product
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['error' => '제품을 찾을 수 없습니다.']);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '서버 오류가 발생했습니다.']);
}