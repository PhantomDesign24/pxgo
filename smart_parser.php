<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(60);

class SmartNanoParser {
    private $base_url = "https://www.nanomemory.co.kr";
    private $visited = []; // 방문한 카테고리 추적
    private $collectedProducts = []; // 수집된 제품 추적 (중복 방지)
    
    /**
     * HTML 가져오기
     */
    private function getHtml($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
        ]);
        
        $html = curl_exec($ch);
        curl_close($ch);
        
        if (!$html) return false;
        
        // 인코딩 변환
        if (mb_detect_encoding($html, ['UTF-8', 'EUC-KR'], true) !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', 'EUC-KR');
        }
        
        return $html;
    }
    
    /**
     * 카테고리가 최종 카테고리인지 확인 (strong 태그 체크)
     */
    private function isFinalCategory($html, $currentCatcode) {
        // 현재 카테고리가 strong 태그로 표시되어 있는지 확인
        if (preg_match('/<dd>\s*<a[^>]*catcode=' . $currentCatcode . '[^>]*>\s*<strong>/i', $html)) {
            return true;
        }
        return false;
    }
    
    /**
     * 서브카테고리 파싱 (재귀적)
     */
    public function getSubCategories($catcode, $depth = 0, $maxDepth = 3, $parentPath = '') {
        // 이미 방문한 카테고리는 스킵
        if (isset($this->visited[$catcode]) || $depth >= $maxDepth) {
            return [];
        }
        
        $this->visited[$catcode] = true;
        
        $url = "{$this->base_url}/product/product.php?ptype=list&catcode={$catcode}";
        $html = $this->getHtml($url);
        
        if (!$html) return [];
        
        $categories = [];
        
        // 이 카테고리가 최종 카테고리인지 확인
        if ($this->isFinalCategory($html, $catcode)) {
            return []; // 최종 카테고리면 서브카테고리 없음
        }
        
        // cate_area에서 서브카테고리 추출
        if (preg_match('/<div class="cate_area">(.*?)<\/div>\s*<div/s', $html, $match)) {
            // 모든 카테고리 링크 파싱
            preg_match_all('/<dd>\s*<a[^>]*href=[\'"]([^\'"]*catcode=(\d+)[^\'"]*)[\'"][^>]*>(.*?)<\/a>\s*<\/dd>/si', 
                          $match[1], $links, PREG_SET_ORDER);
            
            foreach ($links as $link) {
                $subCatcode = $link[2];
                $linkContent = $link[3];
                
                // 현재 카테고리는 제외
                if ($subCatcode == $catcode) continue;
                
                // strong 태그가 있으면 현재 선택된 카테고리 (최종 카테고리)
                $isStrong = preg_match('/<strong>/i', $linkContent);
                $name = strip_tags($linkContent);
                $name = trim(preg_replace('/\s+/', ' ', $name));
                
                if (!empty($name)) {
                    $fullPath = $parentPath ? $parentPath . ' > ' . $name : $name;
                    
                    $category = [
                        'catcode' => $subCatcode,
                        'name' => $name,
                        'path' => $fullPath,
                        'depth' => $depth + 1,
                        'is_final' => $isStrong
                    ];
                    
                    // strong 태그가 없는 카테고리만 재귀적으로 탐색
                    if (!$isStrong) {
                        $category['subcategories'] = $this->getSubCategories(
                            $subCatcode, 
                            $depth + 1, 
                            $maxDepth, 
                            $fullPath
                        );
                    } else {
                        $category['subcategories'] = [];
                    }
                    
                    $categories[] = $category;
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * 카테고리 트리를 평면 배열로 변환
     */
    private function flattenCategories($categories) {
        $flat = [];
        
        foreach ($categories as $cat) {
            $flat[] = [
                'catcode' => $cat['catcode'],
                'name' => $cat['name'],
                'path' => $cat['path'],
                'depth' => $cat['depth'],
                'is_final' => $cat['is_final'] ?? false
            ];
            
            if (!empty($cat['subcategories'])) {
                $flat = array_merge($flat, $this->flattenCategories($cat['subcategories']));
            }
        }
        
        return $flat;
    }
    
    /**
     * 단일 카테고리 정보 가져오기
     */
    public function getSingleCategory($mainName, $mainCode) {
        $this->visited = []; // 방문 기록 초기화
        
        try {
            // 메인 카테고리
            $mainCategory = [
                'catcode' => $mainCode,
                'name' => '전체',
                'path' => $mainName . ' > 전체',
                'depth' => 0,
                'is_final' => false
            ];
            
            // 서브카테고리 가져오기
            $subCategories = $this->getSubCategories($mainCode, 0, 3, $mainName);
            
            // 평면화
            $flat = [$mainCategory];
            if (!empty($subCategories)) {
                $flat = array_merge($flat, $this->flattenCategories($subCategories));
            }
            
            return [
                'success' => true,
                'code' => $mainCode,
                'categories' => $flat,
                'tree' => $subCategories
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 제품 중복 체크를 위한 고유 키 생성
     */
    private function getProductKey($productName, $price) {
        // 제품명과 가격을 조합하여 고유 키 생성
        return md5($productName . '_' . $price);
    }
    
    /**
     * 제품 파싱 (중복 제거 포함)
     */
    public function parseProductsWithDedup($catcode, $category, $subcategory, $maxPages = 99) {
        $products = [];
        $baseUrl = "https://www.nanomemory.co.kr/product/product.php?ptype=list&catcode={$catcode}";
        
        // 첫 페이지에서 전체 페이지 수 확인
        $html = $this->getHtml($baseUrl);
        
        if (!$html) {
            return ['success' => false, 'error' => '페이지를 가져올 수 없습니다.'];
        }
        
        // 전체 페이지 수 확인
        $totalPages = 1;
        if (preg_match_all('/page=(\d+)/', $html, $pageMatches)) {
            $totalPages = max($pageMatches[1]);
        }
        
        // maxPages로 제한
        $totalPages = min($totalPages, $maxPages);
        
        // 모든 페이지 파싱
        for ($page = 1; $page <= $totalPages; $page++) {
            $url = $baseUrl;
            if ($page > 1) {
                $url .= "&page={$page}";
            }
            
            $html = $this->getHtml($url);
            
            if ($html) {
                // 제품 파싱
                if (preg_match('/<div class="pro_list">(.*?)<\/div>\s*<!--/s', $html, $match)) {
                    preg_match_all('/<tr>(.*?)<\/tr>/s', $match[1], $rows);
                    
                    foreach ($rows[1] as $row) {
                        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cols);
                        
                        if (count($cols[1]) >= 3) {
                            $classification = strip_tags(trim($cols[1][0]));
                            $productName = strip_tags(trim($cols[1][1]));
                            $priceText = strip_tags(trim($cols[1][2]));
                            
                            $price = preg_replace('/[^0-9]/', '', $priceText);
                            
                            if (is_numeric($price) && $price > 0) {
                                $productName = preg_replace('/\s+/', ' ', $productName);
                                $productKey = $this->getProductKey($productName, $price);
                                
								// 중복 체크
                                if (!isset($this->collectedProducts[$productKey])) {
                                    // 소분류 처리 - ' > ' 기준으로 왼쪽 부분만 사용
                                    $cleanSubcategory = $subcategory;
                                    if (strpos($cleanSubcategory, ' > ') !== false) {
                                        $parts = explode(' > ', $cleanSubcategory);
                                        // 첫 번째 부분(대분류) 제거하고 두 번째 부분만 사용
                                        if (count($parts) >= 2) {
                                            $cleanSubcategory = $parts[1];
                                        }
                                    }
                                    
                                    $product = [
                                        '대분류' => $category,
                                        '소분류' => $cleanSubcategory,
                                        '분류' => $classification,
                                        '제품명' => $productName,
                                        '가격' => intval($price)
                                    ];
                                    
                                    $products[] = $product;
                                    $this->collectedProducts[$productKey] = true;
                                }
                            }
                        }
                    }
                }
            }
            
            // 페이지 간 딜레이
            if ($page < $totalPages) {
                usleep(500000); // 0.5초
            }
        }
        
        return [
            'success' => true,
            'products' => $products,
            'totalPages' => $totalPages,
            'duplicatesSkipped' => count($this->collectedProducts) - count($products)
        ];
    }
}

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

// 액션 처리
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// POST 데이터 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $action;
}

// 파서 인스턴스 (전역으로 중복 체크를 위해)
static $globalParser = null;
if (!$globalParser) {
    $globalParser = new SmartNanoParser();
}

switch ($action) {
    case 'getSingleCategory':
        // 단일 카테고리만 가져오기
        $category = $_GET['category'] ?? '';
        $catcode = $_GET['catcode'] ?? '';
        
        if (empty($category) || empty($catcode)) {
            echo json_encode(['success' => false, 'error' => '카테고리 정보가 없습니다.']);
            break;
        }
        
        $result = $globalParser->getSingleCategory($category, $catcode);
        echo json_encode($result);
        break;
        
    case 'getMainCategories':
        // 메인 카테고리 목록만 반환
        echo json_encode([
            'success' => true,
            'categories' => $mainCategories
        ]);
        break;
        
    case 'parseProducts':
        // 제품 파싱 (중복 제거 버전)
        $input = json_decode(file_get_contents('php://input'), true);
        $catcode = $input['catcode'] ?? '';
        $category = $input['category'] ?? '';
        $subcategory = $input['subcategory'] ?? '';
        $maxPages = $input['maxPages'] ?? 99;
        
        if (empty($catcode)) {
            echo json_encode(['success' => false, 'error' => '카테고리 코드가 없습니다.']);
            break;
        }
        
        $result = $globalParser->parseProductsWithDedup($catcode, $category, $subcategory, $maxPages);
        echo json_encode($result);
        break;
        
    case 'resetDuplicateChecker':
        // 중복 체크 초기화 (새로운 파싱 세션 시작시)
        $globalParser = new SmartNanoParser();
        echo json_encode(['success' => true, 'message' => '중복 체크 초기화 완료']);
        break;
        
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'Smart parser ready (with deduplication)',
            'time' => date('Y-m-d H:i:s')
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
}
?>