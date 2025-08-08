<?php
/*
 * 파일명: cron_auto_parser.php
 * 위치: /cron/cron_auto_parser.php
 * 기능: 자동으로 나노메모리 제품을 파싱하고 DB에 저장하는 크론잡
 * 작성일: 2025-08-01
 * 수정일: 2025-08-02
 */

// ===================================
// 초기 설정
// ===================================

/* CLI 환경 확인 */
if (php_sapi_name() !== 'cli') {
    die('이 스크립트는 CLI 환경에서만 실행 가능합니다.');
}

/* 실행 시간 제한 해제 */
set_time_limit(0);
ini_set('memory_limit', '512M');

/* 파일 경로 설정 */
define('BASE_PATH', dirname(__DIR__));
require_once(BASE_PATH . '/db_config.php');
require_once(BASE_PATH . '/smart_parser.php');
require_once(BASE_PATH . '/telegram_config.php');

/* 로그 디렉토리 생성 */
$logDir = BASE_PATH . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// ===================================
// 크론잡 클래스
// ===================================

/**
 * 자동 파싱 크론잡 클래스
 */
class AutoParserCron {
    private $pdo;
    private $parser;
    private $logFile;
    private $startTime;
    private $allProducts = [];
    private $stats = [
        'total_categories' => 0,
        'parsed_categories' => 0,
        'total_products' => 0,
        'new_products' => 0,
        'duplicate_products' => 0,
        'errors' => 0
    ];
    
    /**
     * 생성자
     */
    public function __construct() {
        $this->startTime = time();
        $this->logFile = BASE_PATH . '/logs/cron_' . date('Y-m-d') . '.log';
        $this->pdo = getDB();
        $this->parser = new SmartNanoParser();
        
        $this->log("========================================");
        $this->log("크론잡 시작: " . date('Y-m-d H:i:s'));
        
        // 텔레그램 시작 알림
        sendCronStartNotification('나노메모리 제품 자동 파싱');
    }
    
    /**
     * 메인 실행 함수
     */
    public function run() {
        try {
            // 1. 모든 카테고리 파싱
            $this->log("1단계: 제품 데이터 수집 시작");
            $this->parseAllCategories();
            
            // 2. JSON 파일 저장
            $this->log("2단계: JSON 파일 생성");
            $jsonFile = $this->saveToJson();
            
            // 3. 데이터베이스 저장
            $this->log("3단계: 데이터베이스 저장");
            $this->saveToDatabase();
            
            // 4. 완료 보고
            $this->reportComplete();
            
        } catch (Exception $e) {
            $this->log("오류 발생: " . $e->getMessage(), 'ERROR');
            
            // 텔레그램 오류 알림
            sendCronErrorNotification('나노메모리 제품 자동 파싱', $e->getMessage());
            
            $this->sendErrorNotification($e->getMessage());
        }
    }
    
    /**
     * 모든 카테고리 파싱
     */
    private function parseAllCategories() {
        // 메인 카테고리 정의
        $mainCategories = [
            'CPU' => '10000000',
            'RAM' => '11000000',
            'BOARD' => '12000000',
            'VGA' => '13000000',
            'SSD' => '14000000',
            'HDD' => '16000000',
            'POWER' => '17000000',
            '모니터' => '18000000'
        ];
        
        // 중복 체크를 위한 Map
        $productMap = [];
        
        foreach ($mainCategories as $name => $code) {
            $this->log("카테고리 [{$name}] 처리 시작");
            
            try {
                // 서브카테고리 가져오기
                $categoryData = $this->parser->getSingleCategory($name, $code);
                
                if (!$categoryData['success']) {
                    throw new Exception($categoryData['error']);
                }
                
                $categories = $categoryData['categories'];
                $this->stats['total_categories'] += count($categories);
                
                // 각 서브카테고리의 제품 파싱
                foreach ($categories as $cat) {
                    // "전체" 카테고리는 건너뛰기
                    if ($cat['depth'] === 0 || $cat['path'] === $name . ' > 전체') {
                        $this->log("  - {$cat['path']} 건너뜀 (전체 카테고리)");
                        continue;
                    }
                    
                    // 카테고리별 특별 처리
                    $skipCategory = false;
                    
                    // CPU, RAM, BOARD, VGA는 depth 2 이상만
                    if (in_array($name, ['CPU', 'RAM', 'BOARD', 'VGA'])) {
                        if (!$cat['is_final'] && $cat['depth'] < 2) {
                            $this->log("  - {$cat['path']} 건너뜀 (상위 카테고리)");
                            $skipCategory = true;
                        }
                    }
                    // SSD, HDD, POWER, 모니터는 depth 1도 파싱
                    else {
                        // 이 카테고리들은 depth 1부터 제품이 있을 수 있음
                        if ($cat['depth'] < 1) {
                            $this->log("  - {$cat['path']} 건너뜀 (최상위 카테고리)");
                            $skipCategory = true;
                        }
                    }
                    
                    if ($skipCategory) {
                        continue;
                    }
                    
                    $this->log("  - {$cat['path']} 파싱 중...");
                    
                    $result = $this->parser->parseProductsWithDedup(
                        $cat['catcode'],
                        $name,
                        $cat['path'],
                        99 // 최대 페이지
                    );
                    
                    if ($result['success']) {
                        $this->stats['parsed_categories']++;
                        
                        // 중복 제거하며 제품 추가
                        foreach ($result['products'] as $product) {
                            $productKey = $product['제품명'] . '_' . $product['가격'];
                            
                            if (!isset($productMap[$productKey])) {
                                $productMap[$productKey] = $product;
                                $this->stats['new_products']++;
                            } else {
                                $this->stats['duplicate_products']++;
                            }
                        }
                        
                        $this->log("    ✓ {$result['totalPages']}페이지, " . count($result['products']) . "개 제품");
                    } else {
                        $this->log("    ✗ 오류: " . $result['error'], 'ERROR');
                        $this->stats['errors']++;
                    }
                    
                    // 서버 부하 방지를 위한 딜레이
                    usleep(500000); // 0.5초
                }
                
            } catch (Exception $e) {
                $this->log("카테고리 [{$name}] 오류: " . $e->getMessage(), 'ERROR');
                $this->stats['errors']++;
            }
        }
        
        // Map을 배열로 변환
        $this->allProducts = array_values($productMap);
        $this->stats['total_products'] = count($this->allProducts);
        
        $this->log("수집 완료: 총 " . $this->stats['total_products'] . "개 고유 제품");
    }
    
    /**
     * JSON 파일로 저장
     *
     * @return string 저장된 파일 경로
     */
    private function saveToJson() {
        $dataDir = BASE_PATH . '/data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        $filename = $dataDir . '/nanomemory_products_' . date('Y-m-d_His') . '.json';
        
        $json = json_encode($this->allProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $json);
        
        $this->log("JSON 파일 생성: " . basename($filename));
        
        // 최신 파일 심볼릭 링크 생성 (또는 복사)
        $latestFile = $dataDir . '/latest.json';
        if (file_exists($latestFile)) {
            unlink($latestFile);
        }
        copy($filename, $latestFile);
        
        return $filename;
    }
    
    /**
     * 데이터베이스에 저장
     */
    private function saveToDatabase() {
        $this->log("데이터베이스 저장 시작 (전체 교체 모드)");
        
        // 먼저 테이블이 없으면 생성
        $this->createTableIfNotExists();
        
        try {
            // 트랜잭션 시작
            $this->pdo->beginTransaction();
            
            // 외래 키 체크 임시 비활성화
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->log("외래 키 체크 비활성화");
            
            // 기존 데이터 삭제 (DELETE 사용)
            $this->pdo->exec("DELETE FROM nm_products");
            $this->log("기존 데이터 삭제 완료");
            
            // AUTO_INCREMENT 리셋
            $this->pdo->exec("ALTER TABLE nm_products AUTO_INCREMENT = 1");
            $this->log("AUTO_INCREMENT 리셋 완료");
            
            // 현재 시간
            $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
            $currentTime = $now->format('Y-m-d H:i:s');
            
            // 일괄 삽입을 위한 준비
            $insertSql = "INSERT INTO nm_products 
                         (category_main, category_sub, classification, product_name, price, created_at, updated_at) 
                         VALUES ";
            
            $values = [];
            $params = [];
            $batchSize = 100; // 한 번에 삽입할 레코드 수
            $totalInserted = 0;
            
            foreach ($this->allProducts as $index => $product) {
                $paramIndex = $index * 7; // 각 제품당 7개 필드
                
                $values[] = "(
                    :p{$paramIndex}_1,
                    :p{$paramIndex}_2,
                    :p{$paramIndex}_3,
                    :p{$paramIndex}_4,
                    :p{$paramIndex}_5,
                    :p{$paramIndex}_6,
                    :p{$paramIndex}_7
                )";
                
                $params[":p{$paramIndex}_1"] = $product['대분류'];
                $params[":p{$paramIndex}_2"] = $product['소분류'];
                $params[":p{$paramIndex}_3"] = $product['분류'];
                $params[":p{$paramIndex}_4"] = $product['제품명'];
                $params[":p{$paramIndex}_5"] = $product['가격'];
                $params[":p{$paramIndex}_6"] = $currentTime;
                $params[":p{$paramIndex}_7"] = $currentTime;
                
                // 배치 크기에 도달하거나 마지막 레코드인 경우 삽입
                if (count($values) >= $batchSize || $index === count($this->allProducts) - 1) {
                    $sql = $insertSql . implode(', ', $values);
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $totalInserted += count($values);
                    $this->log("  - {$totalInserted}개 저장 완료");
                    
                    // 초기화
                    $values = [];
                    $params = [];
                }
            }
            
            // 외래 키 체크 재활성화
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->log("외래 키 체크 재활성화");
            
            // 트랜잭션 커밋
            $this->pdo->commit();
            
            $this->log("데이터베이스 저장 완료: 총 {$totalInserted}개 제품");
            
        } catch (Exception $e) {
            // 롤백
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            // 외래 키 체크 재활성화 (에러 발생시에도)
            try {
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $e2) {
                // 무시
            }
            
            throw $e;
        }
    }
    
    /**
     * 완료 보고
     */
    private function reportComplete() {
        $duration = time() - $this->startTime;
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        
        $this->log("========================================");
        $this->log("크론잡 완료 보고");
        $this->log("실행 시간: {$minutes}분 {$seconds}초");
        $this->log("전체 카테고리: " . $this->stats['total_categories']);
        $this->log("파싱 성공: " . $this->stats['parsed_categories']);
        $this->log("총 제품 수: " . $this->stats['total_products']);
        $this->log("신규 제품: " . $this->stats['new_products']);
        $this->log("중복 제거: " . $this->stats['duplicate_products']);
        $this->log("오류 발생: " . $this->stats['errors']);
        $this->log("========================================");
        
        // 텔레그램 완료 알림
        sendCronCompleteNotification(
            '나노메모리 제품 자동 파싱',
            $this->stats,
            $duration
        );
        
        // 성공 알림 (필요시 이메일 등으로 발송)
        // $this->sendSuccessNotification();
    }
    
    /**
     * 테이블 생성 (없는 경우)
     */
    private function createTableIfNotExists() {
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS nm_products (
                id INT(11) NOT NULL AUTO_INCREMENT,
                category_main VARCHAR(100) NOT NULL COMMENT '대분류',
                category_sub VARCHAR(255) NOT NULL COMMENT '소분류',
                classification VARCHAR(100) NOT NULL COMMENT '분류',
                product_name VARCHAR(255) NOT NULL COMMENT '제품명',
                price INT(11) NOT NULL COMMENT '가격',
                created_at DATETIME NOT NULL COMMENT '등록일시',
                updated_at DATETIME NOT NULL COMMENT '수정일시',
                PRIMARY KEY (id),
                INDEX idx_category (category_main, category_sub),
                INDEX idx_product (product_name, price),
                INDEX idx_price (price)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='나노메모리 제품 정보'
        ";
        
        try {
            $this->pdo->exec($createTableSql);
            $this->log("테이블 확인/생성 완료");
        } catch (PDOException $e) {
            // 테이블이 이미 존재하는 경우 무시
            if ($e->getCode() != '42S01') {
                throw $e;
            }
        }
    }
    
    /**
     * 로그 기록
     *
     * @param string $message 로그 메시지
     * @param string $level 로그 레벨
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        
        // 파일에 기록
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // 콘솔 출력 (CLI 환경)
        echo $logMessage;
    }
    
    /**
     * 오류 알림 발송 (선택사항)
     *
     * @param string $error 오류 메시지
     */
    private function sendErrorNotification($error) {
        // 이메일, 슬랙 등으로 알림 발송 구현
        // 예: mail('admin@example.com', '크론잡 오류', $error);
    }
}

// ===================================
// 메인 실행
// ===================================

/* 크론잡 실행 */
$cron = new AutoParserCron();
$cron->run();

echo "\n크론잡 실행 완료\n";
?>