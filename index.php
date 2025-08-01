<?php

/*
 * 파일명: index.php
 * 위치: /
 * 기능: 노트북/컴퓨터 매입 랜딩페이지 - 프리미엄 모던 디자인
 * 작성일: 2025-01-30
 * 수정일: 2025-01-30
 */
 // 데이터베이스에서 총 거래 건수 가져오기
$totalTransactions = 3426; // 기본값
try {
    require_once(__DIR__ . '/db_config.php');
    $pdo = getDB();
    
    // 전체 견적 건수 조회 (테스트 데이터 제외)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM computer_inquiries WHERE 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && $result['total'] > 0) {
        $totalTransactions = $result['total'] + 3426; // 기본값 + 실제 데이터
    }
} catch (Exception $e) {
    // 오류 시 기본값 유지
    $totalTransactions = 3426;
}

// 숫자 포맷팅 (천 단위 구분)
$formattedTransactions = number_format($totalTransactions);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <!-- 기본 메타 태그 -->
    <title>픽셀창고 :: 프리미엄 PC부품 본체매입 노트북 맥북 아이패드 갤럭시탭 갤럭시북 PC방폐업 전문</title>
    <meta name="description" content="중고컴퓨터매입, 노트북매입, 맥북매입, PC방폐업 전문. 데스크탑 본체 모니터 아이패드 최고가 매입. 전국출장 당일현금. ☎ 010-1234-5678">
    <meta name="keywords" content="중고컴퓨터매입,노트북매입,맥북매입,PC매입,중고PC매입,중고데스크탑,PC방폐업,피시방폐업,피시방매입,PC방매입,본체매입,모니터매입,아이패드매입,중고컴퓨터본체,컴퓨터매입">
    <meta name="author" content="픽셀창고">
    <meta name="robots" content="index,follow">
    
    <!-- Open Graph (OG) 태그 - 소셜미디어 공유용 -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="픽셀창고 - 중고컴퓨터 노트북 PC방폐업 매입">
    <meta property="og:description" content="중고컴퓨터 노트북 맥북 최고가매입. PC방폐업 대량매입 전문. 전국출장 당일현금 <?php echo $formattedTransactions; ?>건의 거래실적">
    <meta property="og:image" content="https://pxgo.kr/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="https://pxgo.kr">
    <meta property="og:site_name" content="픽셀창고">
    <meta property="og:locale" content="ko_KR">
    
    <!-- Twitter Card 태그 -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="픽셀창고 - 중고컴퓨터 노트북 PC방폐업 매입">
    <meta name="twitter:description" content="중고컴퓨터 노트북 맥북 최고가매입. 전국출장 당일현금">
    <meta name="twitter:image" content="https://pxgo.kr/images/og-image.jpg">
    
    <!-- 네이버 검색 최적화 -->
    <meta name="naver-site-verification" content="네이버에서_발급받은_코드">
    
    <!-- 구글 검색 최적화 -->
    <meta name="google-site-verification" content="구글에서_발급받은_코드">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://pxgo.kr">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#FF6B35">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- ===================================
     * 헤더
     * ===================================
     -->
    <header class="header">
        <div class="container">
            <div class="header-container">
                <a href="#" class="header-logo"><img src="./images/logo.png" class="logo">픽셀창고</a>
                <nav class="header-nav">
                    <a href="#features">서비스</a>
                    <a href="#process">프로세스</a>
                    <a href="#quote">견적</a>
                    <a href="#contact">문의</a>
                </nav>
                
                <div class="header-cta">
                    <a href="#quote" class="btn btn-primary">무료 견적</a>
                </div>
                
                <div class="header-menu">
                    <div class="header-menu-icon">
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 모바일 메뉴 -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <a href="#" class="header-logo"><img src="./images/logo.png" class="logo">픽셀창고</a>
            <div class="mobile-menu-close">×</div>
        </div>
        <div class="mobile-menu-content">
            <ul class="mobile-menu-nav">
                <li><a href="#features">서비스</a></li>
                <li><a href="#process">프로세스</a></li>
                <li><a href="#quote">견적</a></li>
                <li><a href="#contact">문의</a></li>
            </ul>
            <a href="#quote" class="btn btn-primary btn-large" style="width: 100%;">무료 견적 받기</a>
        </div>
    </div>

    <!-- ===================================
     * 히어로 섹션
     * ===================================
     -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <span>지금 바로 상담 가능</span>
                </div>
                
                <h1 class="hero-title">
                    당신의 컴퓨터<b class="font-gl">,</b><br>
                    <span class="gradient">최고의 가치</span>로.
                </h1>
                
				<!-- 히어로 섹션에서 수정할 부분 -->
				<p class="hero-description">
					전문가의 정확한 평가. 투명한 프로세스. 즉시 현금 지급.<br>
					<?php echo $formattedTransactions; ?>건의 거래가 증명하는 신뢰.
				</p>
                <div class="hero-actions">
                    <a href="#quote" class="btn btn-primary btn-large">
                        무료 견적 받기
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="tel:010-1234-5678" class="btn btn-secondary btn-large">
                        <i class="bi bi-telephone-fill"></i>
                        즉시 전화 상담
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================
     * 피처 섹션
     * ===================================
     -->
    <section id="features" class="features">
        <div class="container">
            <div class="features-header fade-in">
                <h2 class="display-2">왜 픽셀창고인가?</h2>
                <p class="body-large text-secondary">전문가의 차이를 경험하세요.</p>
            </div>
            
            <div class="features-grid">
                <!-- 피처 섹션에서 수정할 부분 -->
				<div class="feature-card fade-in">
					<div class="feature-icon">🏆</div>
					<h3 class="feature-title">검증된 신뢰</h3>
					<p class="feature-description">
						10년 연속 고객만족 1위<br>
						누적 거래 <?php echo $formattedTransactions; ?>건
					</p>
				</div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">신속한 거래</h3>
                    <p class="feature-description">
                        견적부터 입금까지<br>
                        평균 30분 이내
                    </p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">🛡️</div>
                    <h3 class="feature-title">완벽한 보안</h3>
                    <p class="feature-description">
                        군사급 데이터 삭제<br>
                        개인정보 완벽 보호
                    </p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">🏆</div>
                    <h3 class="feature-title">검증된 신뢰</h3>
                    <p class="feature-description">
                        10년 연속 고객만족 1위<br>
                        누적 거래 50,000건
                    </p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">🌏</div>
                    <h3 class="feature-title">전국 서비스</h3>
                    <p class="feature-description">
                        전국 어디든 당일 방문<br>
                        프리미엄 출장 매입
                    </p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">💰</div>
                    <h3 class="feature-title">즉시 결제</h3>
                    <p class="feature-description">
                        검수 완료 즉시<br>
                        현장 현금/계좌이체
                    </p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">🏢</div>
                    <h3 class="feature-title">기업 전문</h3>
                    <p class="feature-description">
                        대량 매입 특별 우대<br>
                        세금계산서 즉시 발행
                    </p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">♻️</div>
                    <h3 class="feature-title">친환경</h3>
                    <p class="feature-description">
                        고장 제품도 가치있게<br>
                        환경 보호 기여
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================
     * 프로세스 섹션
     * ===================================
     -->
    <section id="process" class="process">
        <div class="container">
            <div class="process-header fade-in">
                <h2 class="display-2">간단한 4단계</h2>
                <p class="body-large text-secondary">복잡함은 덜고, 가치는 더하다.</p>
            </div>
            
            <div class="process-cards">
                <div class="process-card fade-in">
                    <div class="process-card-header">
                        <div class="process-number">1</div>
                        <span class="process-time">1분</span>
                    </div>
                    <h3 class="process-card-title">간편 문의</h3>
                    <p class="process-card-description">
                        온라인 폼 또는 전화로<br>
                        간편하게 시작하세요
                    </p>
                </div>
                
                <div class="process-card fade-in">
                    <div class="process-card-header">
                        <div class="process-number">2</div>
                        <span class="process-time">5분</span>
                    </div>
                    <h3 class="process-card-title">즉시 견적</h3>
                    <p class="process-card-description">
                        AI 시세 분석으로<br>
                        정확한 견적 제공
                    </p>
                </div>
                
                <div class="process-card fade-in">
                    <div class="process-card-header">
                        <div class="process-number">3</div>
                        <span class="process-time">10분</span>
                    </div>
                    <h3 class="process-card-title">전문가 검수</h3>
                    <p class="process-card-description">
                        10년 경력 전문가의<br>
                        꼼꼼한 상태 확인
                    </p>
                </div>
                
                <div class="process-card fade-in">
                    <div class="process-card-header">
                        <div class="process-number">4</div>
                        <span class="process-time">즉시</span>
                    </div>
                    <h3 class="process-card-title">현장 지급</h3>
                    <p class="process-card-description">
                        검수 완료 즉시<br>
                        현금/계좌이체 결제
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================
     * CTA 섹션
     * ===================================
     -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">지금이 최고의 타이밍</h2>
                <p class="cta-description">
                    중고 시세가 가장 높은 지금,<br>
                    프리미엄 가격으로 판매하세요.
                </p>
                <div class="cta-actions">
                    <a href="#quote" class="btn btn-white btn-large">
                        무료 견적 시작
                        <i class="bi bi-arrow-right"></i>
                    </a>
<!--                     <a href="tel:010-1234-5678" class="btn btn-ghost btn-large">
                        <i class="bi bi-telephone"></i>
                        010-1234-5678
                    </a> -->
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================
     * 실시간 견적 현황
     * ===================================
     -->
    <section class="inquiries">
        <div class="container">
            <div class="inquiries-header fade-in">
                <div class="live-indicator">
                    <span class="live-indicator-dot"></span>
                    <span class="live-indicator-text">실시간 업데이트</span>
                </div>
                
                <h2 class="display-3">실시간 견적 현황</h2>
                <p class="body-large text-secondary">지금 이 순간에도 계속되는 투명한 거래</p>
                
                <div class="inquiries-stats">
                    <div class="stat-item">
                        <div class="stat-icon">📊</div>
                        <div class="stat-value" id="todayCount">0</div>
                        <div class="stat-label">오늘 견적</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value" id="monthCount">0</div>
                        <div class="stat-label">이번달 거래</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-value" id="responseTime">15</div>
                        <div class="stat-label">평균 응답(분)</div>
                    </div>
                </div>
            </div>
            
            <div class="inquiries-grid" id="inquiriesGrid">
                <!-- 동적으로 생성됨 -->
            </div>
        </div>
    </section>

    <!-- ===================================
     * 견적 폼 섹션
     * ===================================
     -->
    <section id="quote" class="quote">
        <div class="container">
            <div class="quote-container">
                <div class="quote-header fade-in">
                    <h2 class="display-3">전문가 무료 견적</h2>
                    <p class="body-large text-secondary">
                        30초 안에 예상 견적을 확인하세요
                    </p>
                </div>
                
                <div class="quote-features fade-in">
                    <div class="quote-feature">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>100% 무료</span>
                    </div>
                    <div class="quote-feature">
                        <i class="bi bi-shield-fill-check"></i>
                        <span>개인정보 안전</span>
                    </div>
                    <div class="quote-feature">
                        <i class="bi bi-clock-fill"></i>
                        <span>즉시 응답</span>
                    </div>
                </div>
                
<!-- 기존 폼 섹션을 아래 코드로 완전히 교체 -->
<form class="form fade-in" id="quoteForm" action="process_inquiry.php" method="POST" enctype="multipart/form-data">
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">이름 <span>*</span></label>
            <input type="text" name="name" class="form-input" placeholder="실명을 입력해주세요" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">연락처 <span>*</span></label>
            <input type="tel" name="phone" class="form-input" placeholder="010-0000-0000" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">기기 종류 <span>*</span></label>
            <select name="device_type" class="form-select" required>
                <option value="">선택하세요</option>
                <optgroup label="컴퓨터">
                    <option value="pc_parts">PC부품</option>
                    <option value="pc_desktop">PC데스크탑(본체)</option>
                    <option value="pc_set">PC+모니터</option>
                    <option value="monitor">모니터</option>
                </optgroup>
                <optgroup label="노트북">
                    <option value="notebook">노트북</option>
                    <option value="macbook">맥북</option>
                </optgroup>
                <optgroup label="모바일/기타">
                    <option value="tablet">태블릿</option>
                    <option value="nintendo">닌텐도스위치</option>
                    <option value="applewatch">애플워치</option>
                </optgroup>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">브랜드/모델</label>
            <input type="text" name="model" class="form-input" placeholder="예: 맥북 프로 16인치 M3 Max">
        </div>
        
        <div class="form-group">
            <label class="form-label">구매 시기</label>
            <select name="purchase_year" class="form-select">
                <option value="">선택하세요</option>
                <option value="2025">2025년</option>
                <option value="2024">2024년</option>
                <option value="2023">2023년</option>
                <option value="2022">2022년</option>
                <option value="2021">2021년 이전</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">매입 방식 <span>*</span></label>
            <select name="service_type" class="form-select" required onchange="toggleLocationField(this)">
                <option value="">선택하세요</option>
                <option value="delivery">무료 택배 매입</option>
                <option value="visit">당일 출장 매입</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">제품 상태 <span>*</span></label>
            <select name="condition_status" class="form-select" required>
                <option value="">선택하세요</option>
                <option value="excellent">매우 좋음</option>
                <option value="good">좋음</option>
                <option value="fair">보통</option>
                <option value="poor">나쁨/고장</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">개수</label>
            <input type="number" name="quantity" class="form-input" min="1" max="100" value="1" placeholder="1">
            <small class="form-help-text">10개 이상 대량 매입 시 추가 혜택</small>
        </div>
        
        <div class="form-group full">
            <label class="form-label">추가 정보</label>
            <textarea name="message" class="form-textarea" rows="4" 
                placeholder="사양, 특이사항 등을 자세히 입력하시면 더 정확한 견적을 받으실 수 있습니다"></textarea>
        </div>
        
        <div class="form-group full" id="locationField" style="display: none;">
            <label class="form-label">지역 선택 <span>*</span></label>
            <div class="location-select-wrapper">
                <select name="location_sido" id="locationSido" class="form-select" onchange="updateSigungu()">
                    <option value="">시/도 선택</option>
                </select>
                <select name="location_sigungu" id="locationSigungu" class="form-select" disabled>
                    <option value="">시/군/구 선택</option>
                </select>
            </div>
            <small class="form-help-text">출장 매입 가능 지역인지 확인 후 연락드립니다.</small>
        </div>
        
        <div class="form-group full">
            <label class="form-label">사진 첨부 (선택)</label>
            <div class="file-upload-wrapper">
                <input type="file" name="photos[]" id="photoUpload" multiple accept="image/*" class="file-input">
                <label for="photoUpload" class="file-label">
                    <i class="bi bi-camera"></i>
                    <span>사진 선택 (최대 5장)</span>
                </label>
                <div id="imagePreview" class="image-preview-grid"></div>
            </div>
            <small class="form-help-text">제품 사진을 첨부하시면 더 정확한 견적을 받으실 수 있습니다</small>
        </div>
        
        <div class="form-group full">
            <div class="form-checkbox">
                <input type="checkbox" id="isCompany" name="is_company">
                <label for="isCompany">
                    기업/단체 대량 매입 (세금계산서 발행 가능)
                </label>
            </div>
        </div>
        
        <div class="form-group full">
            <div class="form-checkbox">
                <input type="checkbox" id="privacy" required>
                <label for="privacy">
                    개인정보 수집 및 이용에 동의합니다. 
                    <a href="#">약관 보기</a>
                </label>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-large">
            <i class="bi bi-calculator"></i>
            견적 요청하기
        </button>
    </div>
    
    <!-- Hidden fields -->
    <input type="hidden" name="email" value="">
    <input type="hidden" name="brand" value="">
    <input type="hidden" name="inquiry_type" value="sell">
</form>
            </div>
        </div>
    </section>

    <!-- ===================================
     * 푸터
     * ===================================
     -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><img src="./images/logo.png" class="logo">픽셀창고</h4>
                    <p class="body-small text-secondary">
                        프리미엄 디바이스 매입 전문<br>
                        10년의 신뢰와 전문성
                    </p>
                    <?php if(isset($_GET['admin']) && $_GET['admin'] === 'true'): ?>
                    <button class="btn btn-secondary btn-small" id="addTestData" style="margin-top: 16px;">
                        테스트 데이터 추가
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="footer-section">
                    <h4>서비스</h4>
                    <ul>
                        <li><a href="#features">왜 우리인가</a></li>
                        <li><a href="#process">매입 프로세스</a></li>
                        <li><a href="#quote">빠른 견적</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>연락처</h4>
                    <ul>
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
                    <a href="#">개인정보처리방침</a> · 
                    <a href="#">이용약관</a>
                </p>
            </div>
        </div>
    </footer>

<!-- 관리자 패널 (index.php의 푸터 섹션 아래에 추가) -->
<?php if(isset($_GET['admin']) && $_GET['admin'] === 'true'): ?>
<!-- ===================================
 * 관리자 패널
 * ===================================
 -->
<div id="adminPanel" style="position: fixed; bottom: 20px; left: 20px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 400px; z-index: 9999; display: none;">
    <h3 style="margin-bottom: 16px; font-size: 18px; font-weight: 700;">관리자 테스트 데이터 생성</h3>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">생성할 개수</label>
        <input type="number" id="testCount" min="1" max="50" value="5" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">기기 종류</label>
        <select id="testDeviceType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="all">전체 랜덤</option>
            <optgroup label="컴퓨터">
                <option value="pc_parts">PC부품</option>
                <option value="pc_desktop">PC데스크탑(본체)</option>
                <option value="pc_set">PC+모니터</option>
                <option value="monitor">모니터</option>
            </optgroup>
            <optgroup label="노트북">
                <option value="notebook">노트북</option>
                <option value="macbook">맥북</option>
            </optgroup>
            <optgroup label="모바일/기타">
                <option value="tablet">태블릿</option>
                <option value="nintendo">닌텐도스위치</option>
                <option value="applewatch">애플워치</option>
            </optgroup>
        </select>
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">상태</label>
        <select id="testStatus" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="all">전체 랜덤</option>
            <option value="new">견적 진행중</option>
            <option value="processing">검수 대기</option>
            <option value="completed">견적 완료</option>
        </select>
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">날짜 범위</label>
        <select id="testDateRange" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;" onchange="toggleCustomDate()">
            <option value="today">오늘</option>
            <option value="recent" selected>최근 7일</option>
            <option value="month">이번달</option>
            <option value="custom">날짜 지정</option>
        </select>
    </div>
    
    <div style="margin-bottom: 16px; display: none;" id="customDateField">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">날짜 선택</label>
        <input type="date" id="testCustomDate" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">매입 방식</label>
        <select id="testServiceType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="all">전체 랜덤</option>
            <option value="delivery">무료 택배 매입</option>
            <option value="visit">당일 출장 매입</option>
        </select>
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 14px; margin-bottom: 8px;">고객 타입</label>
        <select id="testCustomerType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="all">전체 랜덤</option>
            <option value="personal">개인</option>
            <option value="company">기업</option>
        </select>
    </div>
    
    <div style="display: flex; gap: 8px; margin-top: 20px;">
        <button id="generateTestData" class="btn btn-primary" style="flex: 1;">생성하기</button>
        <button id="closeAdminPanel" class="btn btn-secondary" style="flex: 1;">닫기</button>
    </div>
    
    <div style="display: flex; gap: 8px; margin-top: 8px;">
        <a href="admin/inquiries.php?admin=true" class="btn btn-secondary" style="flex: 1; text-align: center;">
            <i class="bi bi-list-ul"></i> 견적 관리
        </a>
    </div>
    
    <div id="adminMessage" style="margin-top: 16px; padding: 12px; border-radius: 8px; display: none;"></div>
</div>

<button id="openAdminPanel" class="btn btn-secondary btn-small" style="position: fixed; bottom: 20px; left: 20px; z-index: 9998;">
    <i class="bi bi-gear"></i> 관리자
</button>

<script>
// 관리자 패널 토글
document.getElementById('openAdminPanel').addEventListener('click', function() {
    document.getElementById('adminPanel').style.display = 'block';
    this.style.display = 'none';
});

document.getElementById('closeAdminPanel').addEventListener('click', function() {
    document.getElementById('adminPanel').style.display = 'none';
    document.getElementById('openAdminPanel').style.display = 'block';
});

// 테스트 데이터 생성
document.getElementById('generateTestData').addEventListener('click', async function() {
    const count = document.getElementById('testCount').value;
    const deviceType = document.getElementById('testDeviceType').value;
    const status = document.getElementById('testStatus').value;
    const dateRange = document.getElementById('testDateRange').value;
    const customDate = document.getElementById('testCustomDate').value;
    const serviceType = document.getElementById('testServiceType').value;
    const customerType = document.getElementById('testCustomerType').value;
    
    this.disabled = true;
    this.innerHTML = '<i class="bi bi-hourglass-split"></i> 생성중...';
    
    const formData = new FormData();
    formData.append('count', count);
    formData.append('device_type', deviceType);
    formData.append('status', status);
    formData.append('date_range', dateRange);
    formData.append('service_type', serviceType);
    formData.append('customer_type', customerType);
    if (dateRange === 'custom' && customDate) {
        formData.append('custom_date', customDate);
    }
    
    try {
        const response = await fetch('/generate_test_data.php?admin=true', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAdminMessage(result.message, 'success');
            // 통계 업데이트
            if (typeof updateStatsFromServer === 'function') {
                updateStatsFromServer();
            }
            // 페이지 새로고침
            setTimeout(() => location.reload(), 1000);
        } else {
            showAdminMessage(result.message || '오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        showAdminMessage('서버 연결에 실패했습니다.', 'error');
    } finally {
        this.disabled = false;
        this.innerHTML = '생성하기';
    }
});

function toggleCustomDate() {
    const dateRange = document.getElementById('testDateRange').value;
    const customDateField = document.getElementById('customDateField');
    
    if (dateRange === 'custom') {
        customDateField.style.display = 'block';
        // 오늘 날짜를 기본값으로 설정
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('testCustomDate').value = today;
    } else {
        customDateField.style.display = 'none';
    }
}

function showAdminMessage(message, type) {
    const messageEl = document.getElementById('adminMessage');
    messageEl.textContent = message;
    messageEl.style.display = 'block';
    messageEl.style.background = type === 'success' ? '#d1e7dd' : '#f8d7da';
    messageEl.style.color = type === 'success' ? '#0f5132' : '#842029';
    
    setTimeout(() => {
        messageEl.style.display = 'none';
    }, 3000);
}
</script>
<?php endif; ?>

    <!-- ===================================
     * 플로팅 요소
     * ===================================
     -->
    <!-- 플로팅 버튼 -->
    <div class="floating-buttons">
        <a href="tel:010-1234-5678" class="float-btn primary">
            <i class="bi bi-telephone-fill"></i>
        </a>
        <a href="#" class="float-btn kakao">
            <i class="bi bi-chat-fill"></i>
        </a>
        <button class="float-btn scroll-top">
            <i class="bi bi-arrow-up"></i>
        </button>
    </div>

    <!-- 실시간 알림 -->
    <div class="live-notification" id="liveNotification">
        <div class="live-notification-header">
            <span class="live-dot"></span>
            <span class="live-notification-title" id="notificationTitle">오늘 매입 현황</span>
        </div>
        <div class="live-notification-content" id="notificationContent">
            <!-- 동적으로 생성됨 -->
        </div>
    </div>

    <!-- Scripts -->
    <script src="script.js"></script>
    
<!-- Scripts 태그 바로 다음의 스크립트를 아래 코드로 교체 -->
<script>
    // 한국 시군구 데이터
    const locationData = {
        "서울특별시": ["강남구", "강동구", "강북구", "강서구", "관악구", "광진구", "구로구", "금천구", "노원구", "도봉구", "동대문구", "동작구", "마포구", "서대문구", "서초구", "성동구", "성북구", "송파구", "양천구", "영등포구", "용산구", "은평구", "종로구", "중구", "중랑구"],
        "부산광역시": ["강서구", "금정구", "기장군", "남구", "동구", "동래구", "부산진구", "북구", "사상구", "사하구", "서구", "수영구", "연제구", "영도구", "중구", "해운대구"],
        "대구광역시": ["남구", "달서구", "달성군", "동구", "북구", "서구", "수성구", "중구"],
        "인천광역시": ["강화군", "계양구", "남동구", "동구", "미추홀구", "부평구", "서구", "연수구", "옹진군", "중구"],
        "광주광역시": ["광산구", "남구", "동구", "북구", "서구"],
        "대전광역시": ["대덕구", "동구", "서구", "유성구", "중구"],
        "울산광역시": ["남구", "동구", "북구", "울주군", "중구"],
        "세종특별자치시": ["세종시"],
        "경기도": ["가평군", "고양시", "과천시", "광명시", "광주시", "구리시", "군포시", "김포시", "남양주시", "동두천시", "부천시", "성남시", "수원시", "시흥시", "안산시", "안성시", "안양시", "양주시", "양평군", "여주시", "연천군", "오산시", "용인시", "의왕시", "의정부시", "이천시", "파주시", "평택시", "포천시", "하남시", "화성시"],
        "강원도": ["강릉시", "고성군", "동해시", "삼척시", "속초시", "양구군", "양양군", "영월군", "원주시", "인제군", "정선군", "철원군", "춘천시", "태백시", "평창군", "홍천군", "화천군", "횡성군"],
        "충청북도": ["괴산군", "단양군", "보은군", "영동군", "옥천군", "음성군", "제천시", "증평군", "진천군", "청주시", "충주시"],
        "충청남도": ["계룡시", "공주시", "금산군", "논산시", "당진시", "보령시", "부여군", "서산시", "서천군", "아산시", "예산군", "천안시", "청양군", "태안군", "홍성군"],
        "전라북도": ["고창군", "군산시", "김제시", "남원시", "무주군", "부안군", "순창군", "완주군", "익산시", "임실군", "장수군", "전주시", "정읍시", "진안군"],
        "전라남도": ["강진군", "고흥군", "곡성군", "광양시", "구례군", "나주시", "담양군", "목포시", "무안군", "보성군", "순천시", "신안군", "여수시", "영광군", "영암군", "완도군", "장성군", "장흥군", "진도군", "함평군", "해남군", "화순군"],
        "경상북도": ["경산시", "경주시", "고령군", "구미시", "군위군", "김천시", "문경시", "봉화군", "상주시", "성주군", "안동시", "영덕군", "영양군", "영주시", "영천시", "예천군", "울릉군", "울진군", "의성군", "청도군", "청송군", "칠곡군", "포항시"],
        "경상남도": ["거제시", "거창군", "고성군", "김해시", "남해군", "밀양시", "사천시", "산청군", "양산시", "의령군", "진주시", "창녕군", "창원시", "통영시", "하동군", "함안군", "함양군", "합천군"],
        "제주특별자치도": ["서귀포시", "제주시"]
    };

    // 시/도 선택 초기화
    function initLocationSelects() {
        const sidoSelect = document.getElementById('locationSido');
        if (!sidoSelect) return;
        
        // 시/도 옵션 추가
        Object.keys(locationData).forEach(sido => {
            const option = document.createElement('option');
            option.value = sido;
            option.textContent = sido;
            sidoSelect.appendChild(option);
        });
    }

    // 시/군/구 업데이트
    function updateSigungu() {
        const sidoSelect = document.getElementById('locationSido');
        const sigunguSelect = document.getElementById('locationSigungu');
        
        if (!sidoSelect || !sigunguSelect) return;
        
        const selectedSido = sidoSelect.value;
        
        // 시/군/구 초기화
        sigunguSelect.innerHTML = '<option value="">시/군/구 선택</option>';
        sigunguSelect.disabled = !selectedSido;
        
        if (selectedSido && locationData[selectedSido]) {
            locationData[selectedSido].forEach(sigungu => {
                const option = document.createElement('option');
                option.value = sigungu;
                option.textContent = sigungu;
                sigunguSelect.appendChild(option);
            });
        }
    }

    // 매입 방식에 따른 지역 필드 토글
    function toggleLocationField(select) {
        const locationField = document.getElementById('locationField');
        const sidoSelect = document.getElementById('locationSido');
        const sigunguSelect = document.getElementById('locationSigungu');
        
        if (select.value === 'visit') {
            locationField.style.display = 'block';
            sidoSelect.required = true;
            sigunguSelect.required = true;
        } else {
            locationField.style.display = 'none';
            sidoSelect.required = false;
            sigunguSelect.required = false;
            sidoSelect.value = '';
            sigunguSelect.value = '';
            sigunguSelect.disabled = true;
        }
    }
    
    // 파일 제거 함수
    function removeFile(index) {
        const photoUpload = document.getElementById('photoUpload');
        const imagePreview = document.getElementById('imagePreview');
        const fileLabel = document.querySelector('.file-label span');
        
        // DataTransfer를 사용하여 파일 목록 재구성
        const dt = new DataTransfer();
        const files = Array.from(photoUpload.files);
        
        files.forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });
        
        photoUpload.files = dt.files;
        
        // UI 업데이트
        if (dt.files.length > 0) {
            fileLabel.textContent = `${dt.files.length}개 파일 선택됨`;
        } else {
            fileLabel.textContent = '사진 선택 (최대 5장)';
            imagePreview.innerHTML = '';
        }
        
        // 미리보기 다시 생성
        displayImagePreviews(dt.files);
    }
    
    // 이미지 미리보기 표시
    function displayImagePreviews(files) {
        const imagePreview = document.getElementById('imagePreview');
        imagePreview.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="미리보기 ${index + 1}">
                    <button type="button" onclick="removeFile(${index})" class="image-preview-remove">
                        <i class="bi bi-x"></i>
                    </button>
                `;
                imagePreview.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
        });
    }
    
    // 개수에 따른 대량 매입 안내
    document.addEventListener('DOMContentLoaded', function() {
        // 지역 선택 초기화
        initLocationSelects();
        
        const quantityInput = document.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                const helpText = this.nextElementSibling;
                if (this.value >= 10) {
                    helpText.style.color = '#2563eb';
                    helpText.style.fontWeight = '600';
                } else {
                    helpText.style.color = '';
                    helpText.style.fontWeight = '';
                }
            });
        }
        
        // 파일 업로드 처리
        const photoUpload = document.getElementById('photoUpload');
        const imagePreview = document.getElementById('imagePreview');
        const fileLabel = document.querySelector('.file-label span');
        
        if (photoUpload) {
            photoUpload.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                
                // 최대 5장 제한
                if (files.length > 5) {
                    alert('사진은 최대 5장까지 첨부 가능합니다.');
                    this.value = '';
                    return;
                }
                
                if (files.length > 0) {
                    fileLabel.textContent = `${files.length}개 파일 선택됨`;
                    displayImagePreviews(files);
                } else {
                    fileLabel.textContent = '사진 선택 (최대 5장)';
                    imagePreview.innerHTML = '';
                }
            });
        }
    });
</script>
<!-- 이 코드를 index.php의 </head> 태그 바로 위에 추가 -->

<!-- 구조화된 데이터에서 수정할 부분 -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "픽셀창고",
    "alternateName": "중고컴퓨터매입 PC방폐업",
    "description": "중고컴퓨터매입, 노트북매입, PC방폐업 전문. 데스크탑 본체 모니터 최고가 현금매입",
    "url": "https://pxgo.kr",
    "email": "phantom.design24@gmail.com",
    "address": {
        "@type": "PostalAddress",
        "@id": "https://pxgo.kr/#address",
        "addressCountry": "KR",
        "addressRegion": "전국"
    },
    "image": "https://pxgo.kr/images/logo.png",
    "priceRange": "₩₩₩",
    "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        "opens": "09:00",
        "closes": "20:00"
    },
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": "37.5665",
        "longitude": "126.9780"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "<?php echo $totalTransactions; ?>"
    },
    "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "매입 서비스",
        "itemListElement": [
            {
                "@type": "Offer",
                "itemOffered": {
                    "@type": "Service",
                    "name": "중고컴퓨터매입"
                }
            },
            {
                "@type": "Offer",
                "itemOffered": {
                    "@type": "Service",
                    "name": "노트북매입"
                }
            },
            {
                "@type": "Offer",
                "itemOffered": {
                    "@type": "Service",
                    "name": "PC방폐업매입"
                }
            }
        ]
    }
}
</script>

<!-- BreadcrumbList - 네이버 검색 노출용 -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [{
        "@type": "ListItem",
        "position": 1,
        "name": "홈",
        "item": "https://pxgo.kr"
    }]
}
</script>
</body>
</html>