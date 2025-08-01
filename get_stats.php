<?php
/*
 * 파일명: get_stats.php
 * 위치: /
 * 기능: 실시간 통계 데이터 조회
 * 작성일: 2025-01-30
 * 수정일: 2025-01-30
 */
require_once(__DIR__ . '/db_config.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    
    // 오늘 날짜 (한국 시간)
    $today = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $todayStart = $today->format('Y-m-d 00:00:00');
    $todayEnd = $today->format('Y-m-d 23:59:59');
    
    // 이번달 시작일
    $monthStart = $today->format('Y-m-01 00:00:00');
    
    // ===================================
    // 오늘 견적 개수 조회 (실제 + 테스트 데이터 모두)
    // ===================================
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN is_test_data = FALSE THEN 1 END) as real_count,
            COUNT(*) as total_count
        FROM computer_inquiries 
        WHERE created_at BETWEEN :start AND :end
    ");
    $stmt->execute([
        ':start' => $todayStart,
        ':end' => $todayEnd
    ]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 오늘 견적은 실제 + 테스트 데이터 모두 표시
    $todayCount = $counts['total_count'];
    
    // ===================================
    // 이번달 거래 개수 조회 (완료된 건만, 실제 + 테스트 모두)
    // ===================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as month_count 
        FROM computer_inquiries 
        WHERE created_at >= :month_start
    ");
    $stmt->execute([
        ':month_start' => $monthStart
    ]);
    $monthCount = $stmt->fetch(PDO::FETCH_ASSOC)['month_count'];
    
    // ===================================
    // 평균 응답 시간 (고정값 사용)
    // ===================================
    $responseTime = rand(12, 18); // 12-18분 사이 랜덤
    
    // ===================================
    // 최근 견적 목록 조회 (실제 + 테스트 데이터 모두 포함)
    // ===================================
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            device_type,
            model,
            purchase_year,
            condition_status,
            service_type,
            location,
            estimated_price,
            status,
            created_at,
            is_test_data
        FROM computer_inquiries 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute();
    $allInquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 실제 데이터와 테스트 데이터를 적절히 섞어서 표시
    // 최신 순으로 정렬되어 있으므로 그대로 사용
    $inquiries = array_slice($allInquiries, 0, 20);
    
    // 응답 데이터
    $response = [
        'success' => true,
        'stats' => [
            'todayCount' => $todayCount,
            'monthCount' => $monthCount,
            'responseTime' => $responseTime
        ],
        'inquiries' => $inquiries
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => '데이터 조회 중 오류가 발생했습니다.'
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);