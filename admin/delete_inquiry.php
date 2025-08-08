<?php
/*
 * 파일명: delete_inquiry.php
 * 위치: /admin/delete_inquiry.php
 * 기능: 견적 삭제 처리
 * 작성일: 2025-01-30
 * 수정일: 2025-08-01
 */

session_start();
require_once(__DIR__ . '/../db_config.php');

// 관리자 권한 체크 - 세션 기반으로 변경
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// JSON 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

// ID 검증
if (!$id) {
    exit(json_encode(['success' => false, 'message' => 'ID가 필요합니다.']));
}

try {
    $pdo = getDB();
    
    // 삭제 쿼리
    $stmt = $pdo->prepare("DELETE FROM computer_inquiries WHERE id = :id");
    $result = $stmt->execute([':id' => $id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => '견적이 삭제되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '해당 견적을 찾을 수 없습니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('Delete Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '시스템 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}