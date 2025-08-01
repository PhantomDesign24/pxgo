<?php
/*
 * 파일명: db_config.php
 * 위치: /
 * 기능: 데이터베이스 연결 설정 및 함수
 * 작성일: 2025-01-30
 */

// ===================================
// 데이터베이스 연결 정보
// ===================================
/* 기본 설정 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'purchase');
define('DB_USER', 'purchase');
define('DB_PASS', 'QNKA-Q4lsvesEHyE');

// ===================================
// 시간대 설정
// ===================================
/* 한국 시간대 설정 */
ini_set('date.timezone', 'Asia/Seoul');
date_default_timezone_set('Asia/Seoul');

// ===================================
// PDO 연결 함수
// ===================================
/* 데이터베이스 연결 함수 */
function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // 한국 시간대로 MySQL 세션 설정
        $pdo->exec("SET time_zone = '+09:00'");
        
        return $pdo;
    } catch (PDOException $e) {
        die('데이터베이스 연결 오류: ' . $e->getMessage());
    }
}

// ===================================
// 테이블 생성 SQL
// ===================================
/* 문의 테이블 구조 */
/*
CREATE TABLE IF NOT EXISTS computer_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    device_type ENUM('laptop', 'desktop', 'both') NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(200),
    purchase_year YEAR,
    condition_status ENUM('excellent', 'good', 'fair', 'poor') NOT NULL,
    message TEXT,
    inquiry_type ENUM('sell', 'quote', 'question') NOT NULL DEFAULT 'sell',
    status ENUM('new', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
?>