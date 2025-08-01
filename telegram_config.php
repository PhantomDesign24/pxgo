<?php
/*
 * 파일명: telegram_config.php
 * 위치: /telegram_config.php
 * 기능: 텔레그램 봇 설정 및 알림 함수
 * 작성일: 2025-08-01
 */

// ===================================
// 텔레그램 봇 설정
// ===================================

/* 텔레그램 봇 토큰 (BotFather에서 발급) */
define('TELEGRAM_BOT_TOKEN', '8237124392:AAFoCLI4B4DKYjchy4mnrHVRPOYdGV1rKCg');

/* 텔레그램 채팅 ID (알림 받을 채팅방 ID) */
// 개인 채팅: 123456789
// 그룹 채팅: -123456789  
// 채널: -1001234567890 (채널은 -100으로 시작)
define('TELEGRAM_CHAT_ID', '-1002810736069');

/* 텔레그램 API URL */
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);

// ===================================
// 텔레그램 알림 함수
// ===================================

/**
 * 텔레그램 메시지 전송
 *
 * @param string $message 전송할 메시지
 * @param string $parseMode 파싱 모드 (HTML, Markdown)
 * @return bool 성공 여부
 */
function sendTelegramMessage($message, $parseMode = 'HTML') {
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => TELEGRAM_API_URL . '/sendMessage',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

/**
 * 크론잡 시작 알림
 *
 * @param string $jobName 작업 이름
 * @return bool 성공 여부
 */
function sendCronStartNotification($jobName) {
    $message = "🚀 <b>크론잡 시작</b>\n\n";
    $message .= "작업명: {$jobName}\n";
    $message .= "시작 시간: " . date('Y-m-d H:i:s') . "\n";
    $message .= "서버: " . gethostname();
    
    return sendTelegramMessage($message);
}

/**
 * 크론잡 완료 알림
 *
 * @param string $jobName 작업 이름
 * @param array $stats 통계 정보
 * @param int $duration 실행 시간 (초)
 * @return bool 성공 여부
 */
function sendCronCompleteNotification($jobName, $stats, $duration) {
    $minutes = floor($duration / 60);
    $seconds = $duration % 60;
    
    $message = "✅ <b>크론잡 완료</b>\n\n";
    $message .= "작업명: {$jobName}\n";
    $message .= "실행 시간: {$minutes}분 {$seconds}초\n\n";
    
    $message .= "📊 <b>처리 결과</b>\n";
    $message .= "• 전체 카테고리: " . number_format($stats['total_categories']) . "개\n";
    $message .= "• 파싱 성공: " . number_format($stats['parsed_categories']) . "개\n";
    $message .= "• 총 제품 수: " . number_format($stats['total_products']) . "개\n";
    $message .= "• 신규 제품: " . number_format($stats['new_products']) . "개\n";
    $message .= "• 중복 제거: " . number_format($stats['duplicate_products']) . "개\n";
    
    if ($stats['errors'] > 0) {
        $message .= "\n⚠️ 오류 발생: " . $stats['errors'] . "건";
    }
    
    $message .= "\n\n완료 시간: " . date('Y-m-d H:i:s');
    
    return sendTelegramMessage($message);
}

/**
 * 오류 알림
 *
 * @param string $jobName 작업 이름
 * @param string $error 오류 메시지
 * @return bool 성공 여부
 */
function sendCronErrorNotification($jobName, $error) {
    $message = "❌ <b>크론잡 오류</b>\n\n";
    $message .= "작업명: {$jobName}\n";
    $message .= "발생 시간: " . date('Y-m-d H:i:s') . "\n\n";
    $message .= "오류 내용:\n<code>" . htmlspecialchars($error) . "</code>\n\n";
    $message .= "서버: " . gethostname();
    
    return sendTelegramMessage($message);
}

// ===================================
// 텔레그램 봇 설정 확인 함수
// ===================================

/**
 * 텔레그램 봇 설정 테스트
 *
 * @return array 테스트 결과
 */
function testTelegramBot() {
    $testMessage = "🔧 <b>텔레그램 봇 테스트</b>\n\n";
    $testMessage .= "이 메시지가 보이면 설정이 정상입니다.\n";
    $testMessage .= "테스트 시간: " . date('Y-m-d H:i:s');
    
    $result = sendTelegramMessage($testMessage);
    
    return [
        'success' => $result,
        'bot_token' => substr(TELEGRAM_BOT_TOKEN, 0, 10) . '...',
        'chat_id' => TELEGRAM_CHAT_ID
    ];
}

// ===================================
// 채팅 ID 가져오기 도우미
// ===================================

/*
텔레그램 채팅 ID 확인 방법:

1. 텔레그램에서 봇 검색 후 시작
2. 봇에게 아무 메시지나 전송
3. 브라우저에서 다음 URL 접속:
   https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates
4. "chat":{"id":123456789} 형식으로 채팅 ID 확인
5. 위의 TELEGRAM_CHAT_ID에 설정

그룹 채팅의 경우 ID가 음수일 수 있음 (예: -123456789)
*/
?>