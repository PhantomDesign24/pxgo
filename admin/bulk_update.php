<?php
/*
 * 파일명: bulk_update.php
 * 위치: /admin/bulk_update.php
 * 기능: 견적 일괄 처리 (상태 변경, 삭제)
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

// 파라미터 받기
$action = isset($_POST['action']) ? $_POST['action'] : '';
$ids = isset($_POST['ids']) ? $_POST['ids'] : [];
$status = isset($_POST['status']) ? $_POST['status'] : '';

// 유효성 검증
if (empty($ids) || !is_array($ids)) {
    exit(json_encode(['success' => false, 'message' => '선택된 항목이 없습니다.']));
}

// ID 정수 변환 및 필터링
$ids = array_map('intval', $ids);
$ids = array_filter($ids, function($id) { return $id > 0; });

if (empty($ids)) {
    exit(json_encode(['success' => false, 'message' => '유효한 ID가 없습니다.']));
}

try {
    $pdo = getDB();
    
    // 현재 시간 (한국 시간)
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    switch ($action) {
        case 'update_status':
            if (empty($status)) {
                exit(json_encode(['success' => false, 'message' => '상태를 선택해주세요.']));
            }
            
            $sql = "UPDATE computer_inquiries SET status = ?, updated_at = ? WHERE id IN ($placeholders)";
            $params = array_merge([$status, $current_time], $ids);
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            $message = $stmt->rowCount() . '개의 견적 상태가 변경되었습니다.';
            break;
            
        case 'delete':
            $sql = "DELETE FROM computer_inquiries WHERE id IN ($placeholders)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($ids);
            
            $message = $stmt->rowCount() . '개의 견적이 삭제되었습니다.';
            break;
            
        default:
            exit(json_encode(['success' => false, 'message' => '잘못된 작업입니다.']));
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '처리에 실패했습니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('Bulk Update Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '시스템 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}