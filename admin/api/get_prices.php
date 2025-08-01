<?php
/*
 * 파일명: get_prices.php
 * 위치: /admin/api/get_prices.php
 * 기능: 매입 가격 목록 조회 API
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

try {
    $pdo = getDB();
    
    // 파라미터
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perPage = 50;
    $offset = ($page - 1) * $perPage;
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // WHERE 조건
    $where = ['1=1'];
    $params = [];
    
    if ($category) {
        $where[] = 'nm.category_main = :category';
        $params[':category'] = $category;
    }
    
    if ($search) {
        $where[] = 'nm.product_name LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 전체 개수
    $countSql = "SELECT COUNT(*) FROM nm_products nm 
                 LEFT JOIN purchase_prices pp ON nm.id = pp.nm_product_id
                 WHERE $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    
    // 데이터 조회
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
            WHERE $whereClause
            ORDER BY nm.category_main, nm.product_name
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalCount' => $totalCount
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '서버 오류가 발생했습니다.']);
}