<?php
/*
 * 파일명: generate_test_data.php
 * 위치: /
 * 기능: 관리자용 테스트 데이터 생성
 * 작성일: 2025-01-30
 * 수정일: 2025-01-30
 */

session_start();
require_once('./db_config.php');

// 관리자 권한 체크 (실제 환경에서는 적절한 인증 구현 필요)
if (!isset($_GET['admin']) || $_GET['admin'] !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// 파라미터 검증
$count = isset($_POST['count']) ? intval($_POST['count']) : 1;
$selectedDeviceType = isset($_POST['device_type']) ? $_POST['device_type'] : 'all';
$selectedStatus = isset($_POST['status']) ? $_POST['status'] : 'all';
$dateRange = isset($_POST['date_range']) ? $_POST['date_range'] : 'recent';
$customDate = isset($_POST['custom_date']) ? $_POST['custom_date'] : null;
$selectedServiceType = isset($_POST['service_type']) ? $_POST['service_type'] : 'all';
$selectedCustomerType = isset($_POST['customer_type']) ? $_POST['customer_type'] : 'all';

// 범위 제한
$count = min(max($count, 1), 50); // 1~50개

// 랜덤 이름 생성 함수
function generateRandomName() {
    $lastNames = ['김', '이', '박', '최', '정', '강', '조', '윤', '장', '임', 
                  '한', '오', '서', '신', '권', '황', '안', '송', '전', '홍',
                  '류', '노', '문', '남', '유', '하', '주', '구', '배', '백'];
    
    $firstName1 = ['민', '서', '준', '지', '태', '현', '재', '소', '수', '은',
                   '영', '진', '선', '정', '도', '하', '시', '유', '주', '성'];
    
    $firstName2 = ['수', '연', '호', '원', '영', '지', '우', '진', '현', '희',
                   '아', '준', '서', '빈', '윤', '은', '우', '유', '혁', '민'];
    
    $lastName = $lastNames[array_rand($lastNames)];
    $name = $lastName;
    
    // 2글자 또는 3글자 이름 랜덤 선택
    if (rand(0, 1) == 0) {
        // 2글자 이름
        $name .= $firstName1[array_rand($firstName1)];
    } else {
        // 3글자 이름
        $name .= $firstName1[array_rand($firstName1)] . $firstName2[array_rand($firstName2)];
    }
    
    return $name;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();
    
    $cities = ['서울', '경기', '인천', '부산', '대구', '광주', '대전', '울산', '세종', '제주',
               '수원', '성남', '고양', '용인', '안양', '안산', '의정부', '평택', '파주', '김포'];
    
    $deviceTypes = ['pc_parts', 'pc_desktop', 'pc_set', 'monitor', 'notebook', 'macbook', 'tablet', 'nintendo', 'applewatch'];
    
    $brands = ['애플', '삼성', 'LG', 'ASUS', 'MSI', 'HP', 'Dell', 'Lenovo', '에일리언웨어', 'Razer'];
    
    $models = [
        'pc_parts' => ['RTX 4090', 'RTX 4080', 'RTX 4070 Ti', 'RX 7900 XTX', 'i9-13900K', 'i7-13700K', 'Ryzen 9 7950X'],
        'pc_desktop' => ['게이밍 PC i9-13900K', 'RTX 4090 워크스테이션', '사무용 i5 시스템', 'Ryzen 7 미니PC'],
        'pc_set' => ['게이밍 풀세트 RTX 4080', '사무용 풀세트 i5', '크리에이터 PC 세트'],
        'monitor' => ['LG 울트라기어 27인치', '삼성 오디세이 32인치', 'Dell 4K 모니터', 'ASUS ROG 게이밍'],
        'notebook' => ['LG 그램 17인치', '갤럭시북 프로 360', 'HP 스펙터 x360', 'ASUS ROG 스트릭스'],
        'macbook' => ['맥북 프로 16인치 M3 Max', '맥북 프로 14인치 M3', '맥북 에어 15인치 M2', '맥북 에어 13인치'],
        'tablet' => ['아이패드 프로 12.9', '아이패드 에어 5', '갤럭시탭 S9 울트라', '갤럭시탭 S8+'],
        'nintendo' => ['닌텐도 스위치 OLED', '닌텐도 스위치 라이트', '닌텐도 스위치 일반'],
        'applewatch' => ['애플워치 울트라 2', '애플워치 시리즈 9', '애플워치 SE 2세대']
    ];
    
    $conditions = ['excellent', 'good', 'fair', 'poor'];
    $statuses = ['new', 'processing', 'completed'];
    
    $stmt = $pdo->prepare("
        INSERT INTO computer_inquiries (
            name, phone, email, device_type, brand, model, 
            purchase_year, condition_status, quantity, is_company,
            service_type, location, message, 
            inquiry_type, status, estimated_price, is_test_data,
            ip_address, created_at, updated_at
        ) VALUES (
            :name, :phone, :email, :device_type, :brand, :model,
            :purchase_year, :condition_status, :quantity, :is_company,
            :service_type, :location, :message,
            :inquiry_type, :status, :estimated_price, :is_test_data,
            :ip_address, :created_at, :updated_at
        )
    ");
    
    $generated = [];
    
    for ($i = 0; $i < $count; $i++) {
        $name = generateRandomName(); // 랜덤 이름 생성
        $city = $cities[array_rand($cities)];
        
        // 기기 종류 선택
        if ($selectedDeviceType === 'all') {
            $deviceType = $deviceTypes[array_rand($deviceTypes)];
        } else {
            $deviceType = $selectedDeviceType;
        }
        
        $modelArray = $models[$deviceType];
        $model = $modelArray[array_rand($modelArray)];
        $brand = '';
        
        // 브랜드 추출
        if (strpos($model, '맥북') !== false || strpos($model, '아이패드') !== false || strpos($model, '애플워치') !== false) {
            $brand = '애플';
        } elseif (strpos($model, '갤럭시') !== false) {
            $brand = '삼성';
        } elseif (strpos($model, 'LG') !== false) {
            $brand = 'LG';
        } elseif (strpos($model, '닌텐도') !== false) {
            $brand = '닌텐도';
        } else {
            $brand = $brands[array_rand($brands)];
        }
        
        $purchaseYear = rand(2020, 2025);
        $condition = $conditions[array_rand($conditions)];
        
        // 상태 선택
        if ($selectedStatus === 'all') {
            $status = $statuses[array_rand($statuses)];
        } else {
            $status = $selectedStatus;
        }
        
        // 서비스 타입 선택
        if ($selectedServiceType === 'all') {
            $serviceType = rand(0, 1) ? 'delivery' : 'visit';
        } else {
            $serviceType = $selectedServiceType;
        }
        
        $location = $serviceType === 'visit' ? $city : null;
        
        // 고객 타입에 따른 개수와 기업 여부 설정
        if ($selectedCustomerType === 'personal') {
            $quantity = rand(1, 3); // 개인은 1-3개
            $isCompany = false;
        } elseif ($selectedCustomerType === 'company') {
            $quantity = rand(5, 30); // 기업은 5-30개
            $isCompany = true;
        } else {
            // 랜덤
            $quantity = rand(1, 100) < 90 ? rand(1, 3) : rand(5, 20);
            $isCompany = $quantity >= 10 || rand(1, 100) < 20;
        }
        
        // 날짜 설정
        $createdAt = new DateTime('now', new DateTimeZone('Asia/Seoul'));
        
        switch ($dateRange) {
            case 'today':
                // 오늘 날짜 (시간과 분만 랜덤)
                $hoursAgo = rand(0, (int)$createdAt->format('H')); // 현재 시간까지만
                $minutesAgo = rand(0, 59);
                $createdAt->setTime($createdAt->format('H') - $hoursAgo, $createdAt->format('i') - $minutesAgo);
                break;
                
            case 'recent':
                // 최근 7일 이내
                $daysAgo = rand(0, 6);
                $hoursAgo = rand(0, 23);
                $minutesAgo = rand(0, 59);
                $createdAt->sub(new DateInterval("P{$daysAgo}DT{$hoursAgo}H{$minutesAgo}M"));
                break;
                
            case 'month':
                // 이번달 내
                $currentDay = (int)$createdAt->format('j'); // 오늘이 몇일인지
                $daysAgo = rand(0, $currentDay - 1); // 1일부터 오늘까지
                $hoursAgo = rand(0, 23);
                $minutesAgo = rand(0, 59);
                $createdAt->sub(new DateInterval("P{$daysAgo}DT{$hoursAgo}H{$minutesAgo}M"));
                break;
                
            case 'custom':
                // 사용자 지정 날짜
                if ($customDate) {
                    $createdAt = new DateTime($customDate . ' ' . rand(0, 23) . ':' . rand(0, 59) . ':' . rand(0, 59), new DateTimeZone('Asia/Seoul'));
                }
                break;
        }
        
        $createdAtStr = $createdAt->format('Y-m-d H:i:s');
        
        // 가격 설정
        $basePrice = rand(30, 300);
        $price = null;
        
        if ($status === 'completed') {
            switch ($condition) {
                case 'excellent':
                    $price = $basePrice;
                    break;
                case 'good':
                    $price = intval($basePrice * 0.85);
                    break;
                case 'fair':
                    $price = intval($basePrice * 0.70);
                    break;
                case 'poor':
                    $price = intval($basePrice * 0.50);
                    break;
            }
            
            // 대량 매입 할인
            if ($quantity >= 10) {
                $price = intval($price * 1.1); // 10% 추가
            }
        }
        
        $data = [
            ':name' => $name,
            ':phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
            ':email' => strtolower(str_replace(' ', '', $name)) . rand(100, 999) . '@test.com',
            ':device_type' => $deviceType,
            ':brand' => $brand,
            ':model' => $model,
            ':purchase_year' => $purchaseYear,
            ':condition_status' => $condition,
            ':quantity' => $quantity,
            ':is_company' => $isCompany ? 1 : 0,  // boolean을 1/0으로 변환
            ':service_type' => $serviceType,
            ':location' => $location,
            ':message' => '테스트 데이터입니다.' . ($isCompany ? ' (기업 대량 매입)' : ''),
            ':inquiry_type' => 'sell',
            ':status' => $status,
            ':estimated_price' => $price,
            ':is_test_data' => 1,  // true를 1로 변환
            ':ip_address' => '127.0.0.1',
            ':created_at' => $createdAtStr,
            ':updated_at' => $createdAtStr
        ];
        
        $stmt->execute($data);
        
        $generated[] = [
            'name' => $name,
            'location' => $location ?: '택배',
            'device' => $model,
            'quantity' => $quantity,
            'price' => $price ? $price . '만원' : '산정중',
            'status' => $status,
            'service_type' => $serviceType === 'delivery' ? '택배' : '출장',
            'is_company' => $isCompany ? '기업' : '개인',
            'date' => $createdAt->format('Y-m-d H:i:s')
        ];
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $count . '개의 테스트 데이터가 생성되었습니다.',
        'generated' => $generated
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => '데이터 생성 중 오류가 발생했습니다: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}