<?php
/*
 * 파일명: process_inquiry.php
 * 위치: /
 * 기능: 문의 처리 - 개수 및 기업 여부 포함
 * 작성일: 2025-01-30
 * 수정일: 2025-01-30
 */
require_once(__DIR__ . '/db_config.php');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
}

// ===================================
// 입력 데이터 검증
// ===================================
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$device_type = $_POST['device_type'] ?? '';
$brand = trim($_POST['brand'] ?? '');
$model = trim($_POST['model'] ?? '');
$purchase_year = $_POST['purchase_year'] ?? null;
$condition_status = $_POST['condition_status'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1); // 개수 필드 추가
$is_company = isset($_POST['is_company']) ? 1 : 0; // 기업 여부 추가
$service_type = $_POST['service_type'] ?? '';
$location = trim($_POST['location'] ?? '');
$message = trim($_POST['message'] ?? '');
$inquiry_type = $_POST['inquiry_type'] ?? 'sell';

// 개수 유효성 검증
if ($quantity < 1) $quantity = 1;
if ($quantity > 100) $quantity = 100;

// 필수 항목 검증
if (empty($name) || empty($phone) || empty($device_type) || empty($condition_status) || empty($service_type)) {
    exit(json_encode(['success' => false, 'message' => '필수 항목을 모두 입력해주세요.']));
}

// 출장 매입 시 지역 필수
if ($service_type === 'visit' && empty($location)) {
    exit(json_encode(['success' => false, 'message' => '출장 매입 시 지역을 입력해주세요.']));
}

// 전화번호 형식 검증
$phone = preg_replace('/[^0-9-]/', '', $phone);
if (!preg_match('/^01[0-9]-[0-9]{3,4}-[0-9]{4}$/', $phone)) {
    exit(json_encode(['success' => false, 'message' => '올바른 전화번호 형식이 아닙니다.']));
}

// ===================================
// 데이터베이스 저장
// ===================================
// ===================================
// 데이터베이스 저장
// ===================================
try {
    $pdo = getDB();
    
    // 현재 시간 (한국 시간)
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // IP 주소
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // 파일 업로드 처리
    $uploaded_files = [];
    if (!empty($_FILES['photos']['name'][0])) {
        $upload_dir = __DIR__ . '/uploads/' . date('Y/m/');
        
        // 업로드 디렉토리 생성
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['photos']['type'][$key];
                $file_size = $_FILES['photos']['size'][$key];
                
                // 파일 타입 검증
                if (!in_array($file_type, $allowed_types)) {
                    continue;
                }
                
                // 파일 크기 검증
                if ($file_size > $max_file_size) {
                    continue;
                }
                
                // 안전한 파일명 생성
                $file_extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // 파일 이동
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $uploaded_files[] = 'uploads/' . date('Y/m/') . $new_filename;
                }
            }
        }
    }
    
    // SQL 쿼리 (quantity와 is_company 필드 추가)
    $sql = "INSERT INTO computer_inquiries (
                name, phone, email, device_type, brand, model, 
                purchase_year, condition_status, quantity, is_company,
                service_type, location, message, 
                inquiry_type, status, estimated_price, is_test_data,
                ip_address, user_agent, created_at, updated_at
            ) VALUES (
                :name, :phone, :email, :device_type, :brand, :model,
                :purchase_year, :condition_status, :quantity, :is_company,
                :service_type, :location, :message,
                :inquiry_type, 'new', NULL, FALSE,
                :ip_address, :user_agent, :created_at, :updated_at
            )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':device_type' => $device_type,
        ':brand' => $brand,
        ':model' => $model,
        ':purchase_year' => $purchase_year,
        ':condition_status' => $condition_status,
        ':quantity' => $quantity,
        ':is_company' => $is_company,
        ':service_type' => $service_type,
        ':location' => $location ?: null,
        ':message' => $message,
        ':inquiry_type' => $inquiry_type,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent,
        ':created_at' => $current_time,
        ':updated_at' => $current_time
    ]);
    
    if ($result) {
        // 파일 정보 저장 (별도 테이블이 있다면 여기서 처리)
        // 예: inquiry_photos 테이블에 저장
        $inquiry_id = $pdo->lastInsertId();
        
        if (!empty($uploaded_files)) {
            $photo_sql = "INSERT INTO inquiry_photos (inquiry_id, file_path, created_at) VALUES (:inquiry_id, :file_path, :created_at)";
            $photo_stmt = $pdo->prepare($photo_sql);
            
            foreach ($uploaded_files as $file_path) {
                $photo_stmt->execute([
                    ':inquiry_id' => $inquiry_id,
                    ':file_path' => $file_path,
                    ':created_at' => $current_time
                ]);
            }
        }
        
        // 대량 매입 안내 메시지 추가
        $additionalMessage = '';
        if ($quantity >= 10) {
            $additionalMessage = ' 대량 매입 전담팀이 곧 연락드릴 예정입니다.';
        }
        if ($is_company) {
            $additionalMessage .= ' 기업 고객님께는 별도의 우대 조건을 안내해 드리겠습니다.';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => '견적 요청이 성공적으로 접수되었습니다.' . $additionalMessage
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => '처리 중 오류가 발생했습니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('Inquiry Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => '시스템 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}