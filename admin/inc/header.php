<?php
/*
 * 파일명: header.php
 * 위치: /admin/inc/header.php
 * 기능: 관리자 페이지 공통 헤더
 * 작성일: 2025-01-31
 */

// 인증 확인
require_once(__DIR__ . '/../check_auth.php');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>픽셀창고 관리자</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="/admin/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- ===================================
     * 상단 헤더
     * ===================================
     -->
    <header class="admin-header">
        <div class="admin-container">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-logo">
                    <a href="/admin/">픽셀창고 <span>ADMIN</span></a>
                </h1>
            </div>
            
            <div class="header-right">
                <div class="header-user">
                    <i class="bi bi-person-circle"></i>
                    <span>관리자</span>
                    <div class="user-dropdown">
                        <a href="/admin/auth.php?logout=1" onclick="return confirm('로그아웃하시겠습니까?')">
                            <i class="bi bi-box-arrow-right"></i> 로그아웃
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- ===================================
     * 사이드바
     * ===================================
     -->
    <aside class="admin-sidebar" id="adminSidebar">
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-title">메인</div>
                <ul class="nav-menu">
                    <li>
                        <a href="/admin/" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>대시보드</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">관리</div>
                <ul class="nav-menu">
                    <li>
                        <a href="/admin/inquiries.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'active' : ''; ?>">
                            <i class="bi bi-inbox"></i>
                            <span>견적 관리</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/price_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'price_management.php' ? 'active' : ''; ?>">
                            <i class="bi bi-currency-dollar"></i>
                            <span>가격 관리</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">시스템</div>
                <ul class="nav-menu">
                    <li>
                        <a href="/admin/settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i>
                            <span>설정</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="/" target="_blank">
                <i class="bi bi-house"></i>
                <span>사이트 바로가기</span>
            </a>
        </div>
    </aside>
    
    <!-- ===================================
     * 메인 컨텐츠 영역
     * ===================================
     -->
    <main class="admin-main">
        <div class="admin-container">