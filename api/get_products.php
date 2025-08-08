<?php
/*
 * 파일명: get_products.php
 * 위치: /api/get_products.php
 * 기능: 카테고리별 제품 조회 API
 * 작성일: 2025-08-01
 * 수정일: 2025-08-02
 */

require_once(__DIR__ . '/../db_config.php');

header('Content-Type: application/json; charset=utf-8');

$category = $_GET['category'] ?? '';

if (empty($category)) {
    exit(json_encode(['success' => false, 'error' => '카테고리를 선택해주세요.']));
}

try {
    $pdo = getDB();
    
    // 활성화된 제품만 조회 (purchase_prices와 조인)
    $sql = "SELECT 
                nm.id,
                nm.category_main,
                nm.category_sub,
                nm.classification,
                nm.product_name,
                nm.price as original_price,
                COALESCE(pp.final_price, nm.price) as final_price
            FROM nm_products nm
            LEFT JOIN purchase_prices pp ON nm.id = pp.nm_product_id
            WHERE nm.category_main = :category
                AND (pp.is_active = 1 OR pp.is_active IS NULL)
            ORDER BY nm.classification, nm.category_sub, nm.product_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category' => $category]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ID를 정수로 변환
    foreach ($products as &$product) {
        $product['id'] = intval($product['id']);
        $product['original_price'] = intval($product['original_price']);
        $product['final_price'] = intval($product['final_price']);
    }
    
    // 카테고리 구조 추출 (선택적 - 프론트엔드 성능 향상용)
    $subCategories = [];
    $classifications = [];
    
    foreach ($products as $product) {
        // 서브카테고리 추출
        if ($product['category_sub']) {
            $parts = explode(' > ', $product['category_sub']);
            if (count($parts) > 1) {
                $subCategory = implode(' > ', array_slice($parts, 1));
                if (!in_array($subCategory, $subCategories)) {
                    $subCategories[] = $subCategory;
                }
            }
        }
        
        // 분류 추출
        if ($product['classification'] && !in_array($product['classification'], $classifications)) {
            $classifications[] = $product['classification'];
        }
    }
    
    sort($subCategories);
    sort($classifications);
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => count($products),
        'filters' => [
            'subCategories' => $subCategories,
            'classifications' => $classifications
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Get Products Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '제품을 불러올 수 없습니다.'
    ], JSON_UNESCAPED_UNICODE);
}