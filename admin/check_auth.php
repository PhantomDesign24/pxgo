<?php
/*
 * 파일명: check_auth.php
 * 위치: /admin/check_auth.php
 * 기능: 관리자 인증 확인
 * 작성일: 2025-08-01
 */

session_start();

// ===================================
// 인증 확인
// ===================================

/**
 * 관리자 로그인 확인
 */
function checkAdminAuth() {
    // 로그인 확인
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        redirectToLogin();
    }
    
    // 세션 타임아웃 확인 (1시간)
    if (isset($_SESSION['admin_login_time'])) {
        $timeout = 3600; // 1시간
        if (time() - $_SESSION['admin_login_time'] > $timeout) {
            session_destroy();
            redirectToLogin();
        }
        
        // 활동 시간 업데이트
        $_SESSION['admin_login_time'] = time();
    }
}

/**
 * 로그인 페이지로 리다이렉트
 */
function redirectToLogin() {
    // 현재 페이지 저장
    $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'];
    
    header('Location: login.php');
    exit;
}

// 모든 관리 페이지에서 이 파일을 include하면 자동으로 인증 확인
checkAdminAuth();
?>