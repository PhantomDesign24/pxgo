<?php
/*
 * 파일명: process_inquiry.php
 * 위치: /
 * 기능: 문의 처리 - 개수 및 기업 여부 포함, 자동견적 처리
 * 작성일: 2025-01-30
 * 수정일: 2025-08-02
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
$message = trim($_POST['message'] ?? '');
$inquiry_type = $_POST['inquiry_type'] ?? 'sell';

// 지역 정보 처리 수정
$location = '';
if ($service_type === 'visit') {
    $location_sido = trim($_POST['location_sido'] ?? '');
    $location_sigungu = trim($_POST['location_sigungu'] ?? '');
    
    if (!empty($location_sido) && !empty($location_sigungu)) {
        $location = $location_sido . ' ' . $location_sigungu;
    } else if (!empty($location_sido)) {
        $location = $location_sido;
    }
}

// 자동견적 데이터 처리
$auto_quote_data = $_POST['auto_quote_data'] ?? null;
$is_auto_quote = false;
$auto_quote_message = '';
$auto_quote_info = null;

// 자동견적 데이터가 있는 경우
if ($auto_quote_data) {
    $is_auto_quote = true;
    $auto_quote_info = json_decode($auto_quote_data, true);
    
    // 자동견적 정보를 메시지에 추가 (데이터베이스 저장용)
    $auto_quote_message = "[자동견적 시스템 선택 제품]\n";
    foreach ($auto_quote_info['products'] as $product) {
        $auto_quote_message .= sprintf(
            "- [%s] %s (%s) - %s원\n",
            $product['classification'],
            $product['product_name'],
            $product['category_sub'],
            number_format($product['final_price'])
        );
    }
    $auto_quote_message .= "\n예상 총 견적가: " . number_format($auto_quote_info['totalPrice']) . "원\n\n";
}

// 메시지 조합 (자동견적 정보 + 사용자 추가 메시지)
$final_message = $auto_quote_message . $message;

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
// 텔레그램 알림을 위한 설정 파일 포함 (파일이 있는 경우만)
if (file_exists(__DIR__ . '/telegram_config.php')) {
    require_once(__DIR__ . '/telegram_config.php');
}

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
    
    // SQL 쿼리
    $sql = "INSERT INTO computer_inquiries (
                name, phone, email, device_type, brand, model, 
                purchase_year, condition_status, quantity, is_company,
                service_type, location, message, 
                inquiry_type, status, estimated_price, is_test_data, is_auto_quote,
                ip_address, user_agent, created_at, updated_at
            ) VALUES (
                :name, :phone, :email, :device_type, :brand, :model,
                :purchase_year, :condition_status, :quantity, :is_company,
                :service_type, :location, :message,
                :inquiry_type, 'new', NULL, FALSE, :is_auto_quote,
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
        ':message' => $final_message,
        ':inquiry_type' => $inquiry_type,
        ':is_auto_quote' => $is_auto_quote ? 1 : 0,  // 명시적으로 정수로 변환
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent,
        ':created_at' => $current_time,
        ':updated_at' => $current_time
    ]);
    
    if ($result) {
        // 파일 정보 저장 (별도 테이블이 있다면 여기서 처리)
        $inquiry_id = $pdo->lastInsertId();
        
        if (!empty($uploaded_files)) {
            // inquiry_photos 테이블이 있는지 확인
            $table_check = $pdo->query("SHOW TABLES LIKE 'inquiry_photos'")->fetch();
            
            if ($table_check) {
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
        }
        
        // ===================================
        // 텔레그램 알림 전송 (함수가 있는 경우만)
        // ===================================
        if (function_exists('sendTelegramMessage')) {
            // 기기 종류 텍스트
            $deviceTypeText = [
                'pc_parts' => 'PC부품',
                'pc_desktop' => 'PC데스크탑',
                'pc_set' => 'PC+모니터',
                'monitor' => '모니터',
                'notebook' => '노트북',
                'macbook' => '맥북',
                'tablet' => '태블릿',
                'nintendo' => '닌텐도스위치',
                'applewatch' => '애플워치'
            ];
            
            // 상태 텍스트
            $conditionText = [
                'excellent' => '매우 좋음',
                'good' => '좋음',
                'fair' => '보통',
                'poor' => '나쁨/고장'
            ];
            
            // 알림 메시지 생성
            if ($is_auto_quote) {
                $telegramMessage = "🔔 <b>새로운 자동견적 요청</b> 🤖\n\n";
                
                // 자동견적 정보 추가
                if ($auto_quote_info) {
                    $telegramMessage .= "📊 <b>자동견적 정보</b>\n";
                    $telegramMessage .= "• 선택 제품: " . count($auto_quote_info['products']) . "개\n";
                    $telegramMessage .= "• 예상 견적가: " . number_format($auto_quote_info['totalPrice']) . "원\n\n";
                }
            } else {
                $telegramMessage = "🔔 <b>새로운 견적 요청</b>\n\n";
            }
            
            $telegramMessage .= "📱 <b>고객 정보</b>\n";
            $telegramMessage .= "• 이름: {$name}\n";
            $telegramMessage .= "• 연락처: {$phone}\n";
            if (!empty($email)) {
                $telegramMessage .= "• 이메일: {$email}\n";
            }
            
            $telegramMessage .= "\n💻 <b>제품 정보</b>\n";
            $telegramMessage .= "• 기기: " . ($deviceTypeText[$device_type] ?? $device_type) . "\n";
            if (!empty($brand)) {
                $telegramMessage .= "• 브랜드: {$brand}\n";
            }
            if (!empty($model)) {
                $telegramMessage .= "• 모델: {$model}\n";
            }
            if ($purchase_year) {
                $telegramMessage .= "• 구매년도: {$purchase_year}년\n";
            }
            $telegramMessage .= "• 상태: " . ($conditionText[$condition_status] ?? $condition_status) . "\n";
            
            if ($quantity > 1) {
                $telegramMessage .= "• 수량: <b>{$quantity}개</b>\n";
            }
            
            if ($is_company) {
                $telegramMessage .= "• 구분: <b>🏢 기업고객</b>\n";
            }
            
            $telegramMessage .= "\n📦 <b>매입 방식</b>\n";
            $telegramMessage .= "• " . ($service_type === 'delivery' ? '무료 택배 매입' : '당일 출장 매입') . "\n";
            if ($service_type === 'visit' && !empty($location)) {
                $telegramMessage .= "• 지역: {$location}\n";
            }
            
            // 메시지 표시
            if (!empty($final_message)) {
                $telegramMessage .= "\n💬 <b>메시지</b>\n";
                $telegramMessage .= htmlspecialchars($final_message) . "\n";
            }
            
            if (!empty($uploaded_files)) {
                $telegramMessage .= "\n📷 사진 " . count($uploaded_files) . "장 첨부\n";
            }
            
            $telegramMessage .= "\n⏰ 접수시간: " . date('Y-m-d H:i:s') . "\n";
            $telegramMessage .= "\n👉 <a href='https://pxgo.kr/admin/inquiries.php'>관리자 페이지에서 확인</a>";
            
            // 텔레그램 메시지 전송
            sendTelegramMessage($telegramMessage);
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
        'message' => '시스템 오류가 발생했습니다. 다시 시도해주세요.'
    ], JSON_UNESCAPED_UNICODE);
}