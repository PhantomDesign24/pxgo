<?php
/*
 * 파일명: auto_quote.php
 * 위치: /
 * 기능: 자동 견적 시스템
 * 작성일: 2025-08-01
 * 수정일: 2025-08-02
 */

require_once(__DIR__ . '/db_config.php');

// 나노메모리 제품 데이터 가져오기
try {
    $pdo = getDB();
    
    // 카테고리별 제품 가져오기
    $categories = [];
    $stmt = $pdo->query("
        SELECT DISTINCT category_main 
        FROM nm_products 
        ORDER BY 
            CASE category_main
                WHEN 'CPU' THEN 1
                WHEN 'RAM' THEN 2
                WHEN 'BOARD' THEN 3
                WHEN 'VGA' THEN 4
                WHEN 'SSD' THEN 5
                WHEN 'HDD' THEN 6
                WHEN 'POWER' THEN 7
                WHEN '모니터' THEN 8
                ELSE 9
            END
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $categories = [];
}

// 모달 모드인지 확인
$isModal = isset($_GET['modal']) && $_GET['modal'] === 'true';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자동 견적 시스템 - 픽셀창고</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="PC부품 실시간 자동견적 시스템. CPU, 그래픽카드, RAM 등 PC부품의 매입 가격을 즉시 확인하세요.">
    <meta name="keywords" content="PC부품매입,자동견적,CPU매입,그래픽카드매입,RAM매입,SSD매입">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auto_quote.css">
    
    <?php if ($isModal): ?>
    <style>
        /* 모달 모드 스타일 */
        body {
            background: #f9fafb;
        }
        
        .header,
        .mobile-menu,
        .footer {
            display: none !important;
        }
        
        .auto-quote-main {
            padding-top: 20px;
            min-height: 100vh;
        }
        
        .page-header {
            display: none;
        }
        
        .quote-system {
            margin-top: 0;
        }
        
        /* 액션 버튼 스타일 조정 */
        .btn-inquiry {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        
        .btn-inquiry:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <?php if (!$isModal): ?>
    <!-- ===================================
     * 헤더 (일반 모드에서만 표시)
     * ===================================
     -->
    <header class="header">
        <div class="container">
            <div class="header-container">
                <a href="/" class="header-logo"><img src="./images/logo.png" class="logo">픽셀창고</a>
                <nav class="header-nav">
                    <a href="/">홈</a>
                    <a href="/auto_quote.php" class="active">자동견적</a>
                    <a href="/#quote">무료견적</a>
                    <a href="/#contact">문의</a>
                </nav>
                
                <div class="header-cta">
                    <a href="tel:02-381-5552" class="btn btn-primary">
                        <i class="bi bi-telephone-fill"></i> 상담전화
                    </a>
                </div>
                
                <div class="header-menu">
                    <div class="header-menu-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 모바일 메뉴 (일반 모드에서만 표시) -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <a href="/" class="header-logo"><img src="./images/logo.png" class="logo">픽셀창고</a>
            <div class="mobile-menu-close">×</div>
        </div>
        <div class="mobile-menu-content">
            <ul class="mobile-menu-nav">
                <li><a href="/">홈</a></li>
                <li><a href="/auto_quote.php" class="active">자동견적</a></li>
                <li><a href="/#quote">무료견적</a></li>
                <li><a href="/#contact">문의</a></li>
            </ul>
            
            <div class="mobile-menu-cta">
                <a href="tel:02-381-5552" class="btn btn-secondary">
                    <i class="bi bi-telephone-fill"></i>
                    전화 상담하기
                </a>
            </div>
            
            <div class="mobile-menu-contact">
                <h4>고객센터</h4>
                <p>02-381-5552</p>
                <span>매일 09:00 - 20:00</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- ===================================
     * 메인 컨텐츠
     * ===================================
     -->
    <main class="auto-quote-main">
        <div class="container">
            <!-- 페이지 헤더 -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-calculator"></i>
                    PC부품 자동 견적 시스템
                </h1>
                <p class="page-desc">
                    보유하신 PC부품을 선택하시면 실시간으로 매입 견적을 확인하실 수 있습니다
                </p>
            </div>

            <!-- 견적 시스템 -->
            <div class="quote-system">
                <!-- 왼쪽: 제품 선택 -->
                <div class="product-selection">
                    <div class="selection-header">
                        <h2>제품 선택</h2>
                        <button class="btn-reset" onclick="resetAll()">
                            <i class="bi bi-arrow-clockwise"></i> 초기화
                        </button>
                    </div>

                    <!-- 카테고리 탭 -->
                    <div class="category-tabs">
                        <?php foreach ($categories as $index => $category): ?>
                        <button class="category-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                                onclick="switchCategory('<?php echo $category; ?>')">
                            <?php echo $category; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

					<!-- 필터 영역 -->
					<div class="filter-section">
						<!-- 동적으로 생성됨 -->
					</div>

                    <!-- 검색바 -->
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="text" id="productSearch" placeholder="제품명 검색..." onkeyup="searchProducts()">
                        <button class="search-clear" onclick="clearSearch()" style="display: none;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <!-- 제품 개수 표시 -->
                    <div class="product-count">
                        <span id="productCount">0개의 제품</span>
                    </div>

                    <!-- 제품 목록 -->
                    <div class="product-list" id="productList">
                        <div class="loading">
                            <i class="bi bi-hourglass-split"></i>
                            <p>제품 목록을 불러오는 중...</p>
                        </div>
                    </div>
                </div>

                <!-- 오른쪽: 견적 결과 -->
                <div class="quote-result">
                    <div class="result-header">
                        <h2>견적 결과</h2>
                    </div>

                    <!-- 선택된 제품 목록 -->
                    <div class="selected-products" id="selectedProducts">
                        <div class="empty-state">
                            <i class="bi bi-cart-x"></i>
                            <p>선택된 제품이 없습니다</p>
                            <span>왼쪽에서 제품을 선택해주세요</span>
                        </div>
                    </div>

                    <!-- 총 견적 -->
                    <div class="total-quote">
                        <div class="quote-row">
                            <span>선택 제품 수</span>
                            <strong id="totalCount">0개</strong>
                        </div>
                        <div class="quote-row">
                            <span>총 매입 견적가</span>
                            <strong id="finalPrice" class="final-price">0원</strong>
                        </div>
                    </div>

                    <!-- 액션 버튼 -->
                    <div class="action-buttons">
                        <button class="btn-print" onclick="printQuote()">
                            <i class="bi bi-printer"></i> 견적서 인쇄
                        </button>
                        <button class="btn-inquiry" onclick="submitInquiry()" disabled>
                            <i class="bi bi-send"></i> 이 견적으로 문의하기
                        </button>
                    </div>

                    <!-- 안내사항 -->
                    <div class="notice-box">
                        <h4><i class="bi bi-info-circle"></i> 안내사항</h4>
                        <ul>
                            <li>표시된 가격은 예상 매입가이며, 실제 검수 후 최종 가격이 확정됩니다</li>
                            <li>제품 상태에 따라 가격이 변동될 수 있습니다</li>
                            <li>대량 매입 시 추가 혜택이 있습니다</li>
                            <li>견적은 실시간 시세를 반영하여 자동 계산됩니다</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if (!$isModal): ?>
    <!-- ===================================
     * 푸터 (일반 모드에서만 표시)
     * ===================================
     -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><img src="./images/logo.png" class="logo">픽셀창고</h4>
                    <p>프리미엄 디바이스 매입 전문<br>10년의 신뢰와 전문성</p>
                </div>
                
                <div class="footer-section">
                    <h4>서비스</h4>
                    <ul>
                        <li><a href="/auto_quote.php">자동견적</a></li>
                        <li><a href="/#quote">무료견적</a></li>
                        <li><a href="/#process">매입 프로세스</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>연락처</h4>
                    <ul>
                        <li><a href="tel:02-381-5552">02-381-5552</a></li>
                        <li><a href="mailto:phantom.design24@gmail.com">phantom.design24@gmail.com</a></li>
                        <li>운영시간: 09:00 - 20:00</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>법적 정보</h4>
                    <ul>
                        <li>상호: 픽셀창고</li>
                        <li>대표: 강성호</li>
                        <li>사업자: 535-68-00113</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="footer-copyright">
                    © 2025 픽셀창고. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
	<?php endif; ?>
    <!-- Scripts -->
    <script src="script.js"></script>
    <script src="auto_quote.js"></script>
	    <?php if ($isModal): ?>
    <script>
    // 모달 모드일 때 submitInquiry 함수 오버라이드
    window.isModalMode = true;
    </script>
    <?php endif; ?>
</body>
</html>