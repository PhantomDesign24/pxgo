<?php
/*
 * 파일명: auth.php
 * 위치: /admin/auth.php
 * 기능: 관리자 인증 처리
 * 작성일: 2025-08-01
 */

session_start();

// ===================================
// 설정
// ===================================

/* 관리자 비밀번호 */
define('ADMIN_PASSWORD', '1234a');

// ===================================
// 로그인 처리
// ===================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if ($password === ADMIN_PASSWORD) {
        // 로그인 성공
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        
        // 원래 요청한 페이지로 리다이렉트
        $redirect = $_SESSION['admin_redirect'] ?? 'index.php';
        unset($_SESSION['admin_redirect']);
        
        header('Location: ' . $redirect);
        exit;
    } else {
        // 로그인 실패
        header('Location: login.php?error=1');
        exit;
    }
}

// ===================================
// 로그아웃 처리
// ===================================

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php?logout=1');
    exit;
}

// 잘못된 접근
header('Location: login.php');
exit;
?>