<?php
/*
 * 파일명: update_inquiry.php
 * 위치: /admin/update_inquiry.php
 * 기능: 견적 정보 전체 업데이트
 * 작성일: 2025-02-01
 */

require_once(__DIR__ . '/../db_config.php');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
}

// 입력 데이터 받기
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$device_type = $_POST['device_type'] ?? '';
$brand = trim($_POST['brand'] ?? '');
$model = trim($_POST['model'] ?? '');
$purchase_year = $_POST['purchase_year'] ?? null;
$condition_status = $_POST['condition_status'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1);
$is_company = isset($_POST['is_company']) ? 1 : 0;
$service_type = $_POST['service_type'] ?? '';
$location = trim($_POST['location'] ?? '');
$status = $_POST['status'] ?? '';
$estimated_price = is_numeric($_POST['estimated_price']) ? intval($_POST['estimated_price']) : null;
$message = trim($_POST['message'] ?? '');
$is_auto_quote = isset($_POST['is_auto_quote']) ? 1 : 0;

// 유효성 검증
if ($id <= 0) {
    exit(json_encode(['success' => false, 'message' => '잘못된 ID입니다.']));
}

if (empty($name) || empty($phone) || empty($device_type) || empty($condition_status) || empty($service_type) || empty($status)) {
    exit(json_encode(['success' => false, 'message' => '필수 항목을 모두 입력해주세요.']));
}

// 전화번호 형식 검증
$phone = preg_replace('/[^0-9-]/', '', $phone);
if (!preg_match('/^01[0-9]-[0-9]{3,4}-[0-9]{4}$/', $phone)) {
    exit(json_encode(['success' => false, 'message' => '올바른 전화번호 형식이 아닙니다.']));
}

// 이메일 형식 검증 (선택사항)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit(json_encode(['success' => false, 'message' => '올바른 이메일 형식이 아닙니다.']));
}

// 개수 유효성 검증
if ($quantity < 1) $quantity = 1;
if ($quantity > 100) $quantity = 100;

// 출장 매입 시 지역 필수
if ($service_type === 'visit' && empty($location)) {
    $location = null; // 또는 오류 처리
}

try {
    $pdo = getDB();
    
    // 현재 시간 (한국 시간)
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $updated_at = $now->format('Y-m-d H:i:s');
    
    // 업데이트 쿼리
    $sql = "UPDATE computer_inquiries SET 
                name = :name,
                phone = :phone,
                email = :email,
                device_type = :device_type,
                brand = :brand,
                model = :model,
                purchase_year = :purchase_year,
                condition_status = :condition_status,
                quantity = :quantity,
                is_company = :is_company,
                service_type = :service_type,
                location = :location,
                status = :status,
                estimated_price = :estimated_price,
                message = :message,
                is_auto_quote = :is_auto_quote,
                updated_at = :updated_at
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email ?: null,
        ':device_type' => $device_type,
        ':brand' => $brand ?: null,
        ':model' => $model ?: null,
        ':purchase_year' => $purchase_year ?: null,
        ':condition_status' => $condition_status,
        ':quantity' => $quantity,
        ':is_company' => $is_company,
        ':service_type' => $service_type,
        ':location' => $location ?: null,
        ':status' => $status,
        ':estimated_price' => $estimated_price,
        ':message' => $message ?: null,
        ':is_auto_quote' => $is_auto_quote,
        ':updated_at' => $updated_at
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '견적 정보가 수정되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '수정에 실패했습니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('Update Inquiry Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '시스템 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}