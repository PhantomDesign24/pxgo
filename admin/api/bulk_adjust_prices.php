<?php
/*
 * 파일명: bulk_adjust_prices.php
 * 위치: /admin/api/bulk_adjust_prices.php
 * 기능: 일괄 가격 조정 API
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
    
    $scope = $input['scope'] ?? 'all';
    $category = $input['category'] ?? '';
    $type = $input['type'] ?? 'percentage';
    $value = floatval($input['value'] ?? 0);
    
    if ($value == 0) {
        exit(json_encode(['error' => '조정값을 입력해주세요.']));
    }
    
    $pdo = getDB();
    
    // 현재 시간
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $currentTime = $now->format('Y-m-d H:i:s');
    
    // WHERE 조건
    $where = ['pp.is_custom = 0']; // 개별 수정된 것은 제외
    $params = [];
    
    if ($scope === 'category' && $category) {
        $where[] = 'nm.category_main = :category';
        $params[':category'] = $category;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 업데이트
    $sql = "
        UPDATE purchase_prices pp
        INNER JOIN nm_products nm ON pp.nm_product_id = nm.id
        SET 
            pp.adjustment_type = :type,
            pp.adjustment_value = :value,
            pp.final_price = CASE 
                WHEN :type2 = 'percentage' THEN 
                    ROUND(nm.price * (1 + :value2 / 100))
                ELSE 
                    nm.price + :value3
            END,
            pp.updated_at = :updated_at
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [
        ':type' => $type,
        ':type2' => $type,
        ':value' => $value,
        ':value2' => $value,
        ':value3' => $value,
        ':updated_at' => $currentTime
    ]));
    
    $updatedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'updated' => $updatedCount,
        'message' => "{$updatedCount}개 제품의 가격이 조정되었습니다."
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Bulk Adjust Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '처리 중 오류가 발생했습니다.']);
}