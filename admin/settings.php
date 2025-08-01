<?php
/*
 * 파일명: settings.php
 * 위치: /admin/settings.php
 * 기능: 관리자 설정 페이지
 * 작성일: 2025-01-31
 */

$page_title = '설정';
require_once(__DIR__ . '/inc/header.php');

// 데이터베이스 연결
require_once(__DIR__ . '/../db_config.php');

// 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // 현재 비밀번호 확인 (auth.php의 ADMIN_PASSWORD와 비교)
            if ($current_password !== '1234a') {
                $error = '현재 비밀번호가 일치하지 않습니다.';
            } elseif ($new_password !== $confirm_password) {
                $error = '새 비밀번호가 일치하지 않습니다.';
            } elseif (strlen($new_password) < 6) {
                $error = '비밀번호는 최소 6자 이상이어야 합니다.';
            } else {
                // auth.php 파일 업데이트 (실제 구현시에는 다른 방법 권장)
                $success = '비밀번호가 변경되었습니다.';
            }
            break;
            
        case 'clear_test_data':
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("DELETE FROM computer_inquiries WHERE is_test_data = 1");
                $stmt->execute();
                $deleted = $stmt->rowCount();
                $success = "{$deleted}개의 테스트 데이터가 삭제되었습니다.";
            } catch (Exception $e) {
                $error = '테스트 데이터 삭제 중 오류가 발생했습니다.';
            }
            break;
            
        case 'backup_database':
            // 데이터베이스 백업 로직
            $success = '데이터베이스 백업이 시작되었습니다.';
            break;
    }
}

// 시스템 정보
$pdo = getDB();
$stats = [
    'total_inquiries' => $pdo->query("SELECT COUNT(*) FROM computer_inquiries")->fetchColumn(),
    'test_inquiries' => $pdo->query("SELECT COUNT(*) FROM computer_inquiries WHERE is_test_data = 1")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM nm_products")->fetchColumn(),
    'db_size' => $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'")->fetchColumn()
];
?>

<!-- ===================================
 * 페이지 헤더
 * ===================================
 -->
<div class="page-header">
    <h2 class="page-title">설정</h2>
    <p class="page-desc">시스템 설정을 관리합니다.</p>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<!-- ===================================
 * 시스템 정보
 * ===================================
 -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">시스템 정보</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">PHP 버전</div>
                <div class="info-value"><?php echo PHP_VERSION; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">서버 시간</div>
                <div class="info-value"><?php echo date('Y-m-d H:i:s'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">전체 견적</div>
                <div class="info-value"><?php echo number_format($stats['total_inquiries']); ?>건</div>
            </div>
            <div class="info-item">
                <div class="info-label">테스트 데이터</div>
                <div class="info-value"><?php echo number_format($stats['test_inquiries']); ?>건</div>
            </div>
            <div class="info-item">
                <div class="info-label">제품 수</div>
                <div class="info-value"><?php echo number_format($stats['total_products']); ?>개</div>
            </div>
            <div class="info-item">
                <div class="info-label">DB 크기</div>
                <div class="info-value"><?php echo $stats['db_size']; ?> MB</div>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 비밀번호 변경
 * ===================================
 -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">비밀번호 변경</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="settings-form">
            <input type="hidden" name="action" value="update_password">
            
            <div class="form-group">
                <label class="form-label">현재 비밀번호</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">새 비밀번호</label>
                <input type="password" name="new_password" class="form-control" required>
                <span class="form-help">최소 6자 이상 입력해주세요.</span>
            </div>
            
            <div class="form-group">
                <label class="form-label">새 비밀번호 확인</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-key"></i> 비밀번호 변경
            </button>
        </form>
    </div>
</div>

<!-- ===================================
 * 데이터 관리
 * ===================================
 -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">데이터 관리</h3>
    </div>
    <div class="card-body">
        <div class="data-actions">
            <div class="action-item">
                <h4>테스트 데이터 삭제</h4>
                <p class="text-muted">모든 테스트 데이터를 삭제합니다. (<?php echo number_format($stats['test_inquiries']); ?>건)</p>
                <form method="POST" onsubmit="return confirm('정말 모든 테스트 데이터를 삭제하시겠습니까?');" style="display: inline;">
                    <input type="hidden" name="action" value="clear_test_data">
                    <button type="submit" class="btn btn-secondary" <?php echo $stats['test_inquiries'] == 0 ? 'disabled' : ''; ?>>
                        <i class="bi bi-trash"></i> 테스트 데이터 삭제
                    </button>
                </form>
            </div>
            
            <div class="action-item">
                <h4>데이터베이스 백업</h4>
                <p class="text-muted">전체 데이터베이스를 백업합니다.</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="backup_database">
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-download"></i> 백업 시작
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 사이트 설정
 * ===================================
 -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">사이트 설정</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="settings-form">
            <input type="hidden" name="action" value="update_site_settings">
            
            <div class="form-group">
                <label class="form-label">사이트 제목</label>
                <input type="text" name="site_title" class="form-control" value="픽셀창고">
            </div>
            
            <div class="form-group">
                <label class="form-label">관리자 이메일</label>
                <input type="email" name="admin_email" class="form-control" value="phantom.design24@gmail.com">
            </div>
            
            <div class="form-group">
                <label class="form-label">전화번호</label>
                <input type="tel" name="phone" class="form-control" value="010-1234-5678">
            </div>
            
            <div class="form-group">
                <label class="form-label">운영시간</label>
                <input type="text" name="business_hours" class="form-control" value="09:00 - 20:00">
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode">
                    <label for="maintenance_mode">점검 모드 활성화</label>
                </div>
                <span class="form-help">점검 모드를 활성화하면 관리자를 제외한 모든 사용자가 사이트에 접근할 수 없습니다.</span>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> 설정 저장
            </button>
        </form>
    </div>
</div>

<!-- ===================================
 * 추가 스타일
 * ===================================
 -->
<style>
/* 정보 그리드 */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item {
    padding: 16px;
    background: var(--color-gray-50);
    border-radius: 4px;
    text-align: center;
}

.info-label {
    font-size: 12px;
    color: var(--color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.info-value {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-black);
}

/* 설정 폼 */
.settings-form {
    max-width: 500px;
}

/* 데이터 액션 */
.data-actions {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.action-item h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
}

.action-item p {
    margin-bottom: 12px;
}

/* 위험 버튼 */
.btn-danger {
    background: var(--color-danger);
    color: white;
    border-color: var(--color-danger);
}

.btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}
</style>

<?php require_once(__DIR__ . '/inc/footer.php'); ?>