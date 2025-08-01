<?php
/*
 * 파일명: update_inquiry.php
 * 위치: /admin/
 * 기능: 견적 정보 수정 처리
 * 작성일: 2025-01-30
 */

session_start();
require_once(__DIR__ . '/../db_config.php');

// 관리자 권한 체크
if (!isset($_GET['admin']) || $_GET['admin'] !== 'true') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// 파라미터 받기
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$estimated_price = isset($_POST['estimated_price']) ? $_POST['estimated_price'] : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// ID 검증
if (!$id) {
    exit(json_encode(['success' => false, 'message' => 'ID가 필요합니다.']));
}

try {
    $pdo = getDB();
    
    // 현재 시간 (한국 시간)
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 업데이트 쿼리
    $sql = "UPDATE computer_inquiries SET ";
    $updates = [];
    $params = [':id' => $id];
    
    if ($status) {
        $updates[] = "status = :status";
        $params[':status'] = $status;
    }
    
    if ($estimated_price !== null && $estimated_price !== '') {
        $updates[] = "estimated_price = :price";
        $params[':price'] = intval($estimated_price);
    }
    
    if ($message !== '') {
        $updates[] = "message = :message";
        $params[':message'] = $message;
    }
    
    $updates[] = "updated_at = :updated_at";
    $params[':updated_at'] = $current_time;
    
    $sql .= implode(', ', $updates) . " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '견적이 수정되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '수정에 실패했습니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('Update Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '시스템 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}