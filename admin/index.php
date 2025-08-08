<?php
/*
 * 파일명: index.php
 * 위치: /admin/index.php
 * 기능: 관리자 대시보드 - 깔끔한 블랙앤화이트 디자인
 * 작성일: 2025-08-01
 */

$page_title = '대시보드';
require_once(__DIR__ . '/inc/header.php');

// 데이터베이스 연결
require_once(__DIR__ . '/../db_config.php');

try {
    $pdo = getDB();
    
    // 오늘 날짜 (2025년 8월 1일 기준)
    $today = new DateTime('2025-08-01', new DateTimeZone('Asia/Seoul'));
    $todayStart = $today->format('Y-m-d 00:00:00');
    $todayEnd = $today->format('Y-m-d 23:59:59');
    
    // 이번달 시작일
    $monthStart = $today->format('Y-m-01 00:00:00');
    
    // 어제 날짜
    $yesterday = clone $today;
    $yesterday->sub(new DateInterval('P1D'));
    $yesterdayStart = $yesterday->format('Y-m-d 00:00:00');
    $yesterdayEnd = $yesterday->format('Y-m-d 23:59:59');
    
    // ===================================
    // 통계 데이터 조회
    // ===================================
    
    // 1. 오늘 견적
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM computer_inquiries WHERE created_at BETWEEN :start AND :end AND is_test_data = 0");
    $stmt->execute([':start' => $todayStart, ':end' => $todayEnd]);
    $todayInquiries = $stmt->fetchColumn();
    
    // 2. 어제 견적 (비교용)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM computer_inquiries WHERE created_at BETWEEN :start AND :end AND is_test_data = 0");
    $stmt->execute([':start' => $yesterdayStart, ':end' => $yesterdayEnd]);
    $yesterdayInquiries = $stmt->fetchColumn();
    
    // 3. 이번달 견적
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM computer_inquiries WHERE created_at >= :start AND is_test_data = 0");
    $stmt->execute([':start' => $monthStart]);
    $monthInquiries = $stmt->fetchColumn();
    
    // 4. 전체 견적
    $totalInquiries = $pdo->query("SELECT COUNT(*) FROM computer_inquiries WHERE is_test_data = 0")->fetchColumn();
    
    // 5. 대기중 견적
    $pendingInquiries = $pdo->query("SELECT COUNT(*) FROM computer_inquiries WHERE status = 'new' AND is_test_data = 0")->fetchColumn();
    
    // 6. 완료된 견적
    $completedInquiries = $pdo->query("SELECT COUNT(*) FROM computer_inquiries WHERE status = 'completed' AND is_test_data = 0")->fetchColumn();
    
    // 7. 평균 견적가
    $avgPrice = $pdo->query("SELECT AVG(estimated_price) FROM computer_inquiries WHERE estimated_price IS NOT NULL AND is_test_data = 0")->fetchColumn();
    $avgPrice = $avgPrice ? round($avgPrice) : 0;
    
    // 8. 나노메모리 제품 수
    $nmProductCount = $pdo->query("SELECT COUNT(*) FROM nm_products")->fetchColumn();
    
    // 9. 최근 견적 목록
    $recentInquiries = $pdo->query("
        SELECT id, name, phone, device_type, status, created_at, is_test_data 
        FROM computer_inquiries 
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // 10. 기기별 통계
    $deviceStats = $pdo->query("
        SELECT device_type, COUNT(*) as count 
        FROM computer_inquiries 
        WHERE is_test_data = 0
        GROUP BY device_type 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. 일별 견적 추이 (최근 7일)
    $dailyStats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = clone $today;
        $date->sub(new DateInterval("P{$i}D"));
        $dateStr = $date->format('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM computer_inquiries 
            WHERE DATE(created_at) = :date AND is_test_data = 0
        ");
        $stmt->execute([':date' => $dateStr]);
        
        $dailyStats[] = [
            'date' => $dateStr,
            'label' => $date->format('m/d'),
            'count' => $stmt->fetchColumn()
        ];
    }
    
    // 12. 상태별 통계
    $statusStats = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM computer_inquiries 
        WHERE is_test_data = 0
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('데이터베이스 오류: ' . $e->getMessage());
}

// 기기 종류 매핑
$deviceTypes = [
    'pc_parts' => 'PC부품',
    'pc_desktop' => 'PC데스크탑',
    'pc_set' => 'PC+모니터',
    'monitor' => '모니터',
    'notebook' => '노트북',
    'macbook' => '맥북',
    'tablet' => '태블릿',
    'nintendo' => '닌텐도스위치',
    'applewatch' => '애플워치'
];

// 상태 매핑
$statusList = [
    'new' => ['text' => '견적 진행중', 'color' => 'warning'],
    'processing' => ['text' => '검수 대기', 'color' => 'info'],
    'completed' => ['text' => '견적 완료', 'color' => 'success'],
    'cancelled' => ['text' => '취소됨', 'color' => 'secondary']
];

// 증감률 계산
$todayChange = $yesterdayInquiries > 0 ? round((($todayInquiries - $yesterdayInquiries) / $yesterdayInquiries) * 100, 1) : 0;
?>

<!-- ===================================
 * 페이지 헤더
 * ===================================
 -->
<div class="page-header">
    <h2 class="page-title">대시보드</h2>
    <p class="page-desc">픽셀창고 관리 시스템 현황을 한눈에 확인하세요.</p>
</div>

<!-- ===================================
 * 주요 통계 카드
 * ===================================
 -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-calendar-day"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($todayInquiries); ?></div>
            <div class="stat-label">오늘 견적</div>
            <?php if ($todayChange != 0): ?>
            <div class="stat-change <?php echo $todayChange > 0 ? 'positive' : 'negative'; ?>">
                <i class="bi bi-arrow-<?php echo $todayChange > 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs($todayChange); ?>%
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-calendar-month"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($monthInquiries); ?></div>
            <div class="stat-label">이번달 견적</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pendingInquiries); ?></div>
            <div class="stat-label">대기중 견적</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi bi-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($completedInquiries); ?></div>
            <div class="stat-label">완료된 견적</div>
        </div>
    </div>
</div>

<!-- ===================================
 * 차트 영역
 * ===================================
 -->
<div class="dashboard-row mb-4">
    <div class="dashboard-col-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 7일 견적 추이</h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="dailyChart" width="800" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-col-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">기기별 통계</h3>
            </div>
            <div class="card-body">
                <div class="device-stats">
                    <?php foreach ($deviceStats as $stat): ?>
                    <div class="device-stat-item">
                        <div class="device-info">
                            <span class="device-name"><?php echo $deviceTypes[$stat['device_type']] ?? $stat['device_type']; ?></span>
                            <span class="device-count"><?php echo number_format($stat['count']); ?>건</span>
                        </div>
                        <div class="device-bar">
                            <div class="device-bar-fill" style="width: <?php echo ($totalInquiries > 0) ? ($stat['count'] / $totalInquiries * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 최근 견적 목록
 * ===================================
 -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">최근 견적</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="100">고객명</th>
                        <th width="120">연락처</th>
                        <th width="120">기기종류</th>
                        <th width="100">상태</th>
                        <th width="120">접수시간</th>
                        <th width="80">액션</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentInquiries as $inquiry): ?>
                    <tr>
                        <td>
                            #<?php echo $inquiry['id']; ?>
                            <?php if ($inquiry['is_test_data']): ?>
                                <span class="badge badge-secondary">TEST</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                        <td>
                            <a href="tel:<?php echo $inquiry['phone']; ?>" class="text-primary">
                                <?php echo htmlspecialchars($inquiry['phone']); ?>
                            </a>
                        </td>
                        <td><?php echo $deviceTypes[$inquiry['device_type']] ?? $inquiry['device_type']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                                <?php echo $statusList[$inquiry['status']]['text'] ?? $inquiry['status']; ?>
                            </span>
                        </td>
                        <td><?php echo date('m/d H:i', strtotime($inquiry['created_at'])); ?></td>
                        <td>
                            <a href="inquiries.php" class="btn btn-secondary btn-sm">
                                <i class="bi bi-eye"></i> 보기
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-3">
            <a href="inquiries.php" class="btn btn-primary">
                전체 견적 보기 <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- ===================================
 * 빠른 액션 & 정보
 * ===================================
 -->
<div class="dashboard-row">
    <div class="dashboard-col-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">빠른 액션</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="inquiries.php?status=new" class="quick-action">
                        <div class="quick-action-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div class="quick-action-content">
                            <div class="quick-action-title">대기중 견적</div>
                            <div class="quick-action-desc">처리 대기중인 <?php echo number_format($pendingInquiries); ?>건</div>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    
                    <a href="price_management.php" class="quick-action">
                        <div class="quick-action-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="quick-action-content">
                            <div class="quick-action-title">가격 관리</div>
                            <div class="quick-action-desc">매입 가격 조정</div>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    
                    <a href="../nano2.php" target="_blank" class="quick-action">
                        <div class="quick-action-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="quick-action-content">
                            <div class="quick-action-title">나노메모리 파서</div>
                            <div class="quick-action-desc">제품 데이터 업데이트</div>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-col-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">시스템 정보</h3>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">전체 견적</span>
                        <span class="info-value"><?php echo number_format($totalInquiries); ?>건</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">평균 견적가</span>
                        <span class="info-value"><?php echo number_format($avgPrice); ?>만원</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">나노메모리 제품</span>
                        <span class="info-value"><?php echo number_format($nmProductCount); ?>개</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">서버 시간</span>
                        <span class="info-value"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 추가 스타일
 * ===================================
 -->
<style>
/* 통계 그리드 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
	margin-bottom:40px;
}

.stat-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.2s;
}

.stat-card:hover {
    border-color: var(--color-gray-400);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 56px;
    height: 56px;
    background: var(--color-gray-100);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--color-gray-700);
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-black);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: var(--color-gray-600);
}

.stat-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
    padding: 2px 8px;
    border-radius: 12px;
}

.stat-change.positive {
    color: var(--color-success);
    background: rgba(40, 167, 69, 0.1);
}

.stat-change.negative {
    color: var(--color-danger);
    background: rgba(220, 53, 69, 0.1);
}

/* 대시보드 그리드 */
.dashboard-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.dashboard-col-8 {
    flex: 0 0 66.666667%;
}

.dashboard-col-6 {
    flex: 0 0 50%;
}

.dashboard-col-4 {
    flex: 0 0 33.333333%;
}

/* 차트 컨테이너 */
.chart-container {
    position: relative;
    height: 300px;
}

/* 기기별 통계 */
.device-stats {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.device-stat-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.device-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.device-name {
    font-size: 13px;
    color: var(--color-gray-700);
    font-weight: 500;
}

.device-count {
    font-size: 13px;
    color: var(--color-gray-600);
    font-weight: 600;
}

.device-bar {
    height: 8px;
    background: var(--color-gray-200);
    border-radius: 4px;
    overflow: hidden;
}

.device-bar-fill {
    height: 100%;
    background: var(--color-black);
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* 빠른 액션 */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--color-gray-50);
    border-radius: 8px;
    text-decoration: none;
    color: var(--color-gray-900);
    transition: all 0.2s;
}

.quick-action:hover {
    background: var(--color-gray-100);
    transform: translateX(4px);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    background: var(--color-white);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--color-gray-700);
}

.quick-action-content {
    flex: 1;
}

.quick-action-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-black);
    margin-bottom: 2px;
}

.quick-action-desc {
    font-size: 12px;
    color: var(--color-gray-600);
}

/* 정보 리스트 */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--color-gray-100);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 14px;
    color: var(--color-gray-600);
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-black);
}

/* 상태 배지 */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-new {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cfe2ff;
    color: #052c65;
}

.status-completed {
    background: #d1e7dd;
    color: #0a3622;
}

.status-cancelled {
    background: #f8d7da;
    color: #58151c;
}

/* 반응형 */
@media (max-width: 1200px) {
    .dashboard-row {
        flex-direction: column;
    }
    
    .dashboard-col-8,
    .dashboard-col-6,
    .dashboard-col-4 {
        flex: 0 0 100%;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-value {
        font-size: 28px;
    }
    
    .quick-action {
        padding: 12px;
    }
    
    .quick-action-icon {
        width: 40px;
        height: 40px;
    }
}
</style>

<!-- ===================================
 * Chart.js 대체 - Canvas로 직접 구현
 * ===================================
 -->
<script>
// 차트 그리기 함수
function drawLineChart() {
    const canvas = document.getElementById('dailyChart');
    const ctx = canvas.getContext('2d');
    
    // 캔버스 크기 설정
    const rect = canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    
    const width = rect.width;
    const height = rect.height;
    const padding = { top: 20, right: 20, bottom: 40, left: 50 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    // 데이터
    const data = [
        <?php foreach ($dailyStats as $stat): ?>
        { label: '<?php echo $stat['label']; ?>', value: <?php echo $stat['count']; ?> },
        <?php endforeach; ?>
    ];
    
    // 최대값 찾기
    const maxValue = Math.max(...data.map(d => d.value), 5);
    const yStep = Math.ceil(maxValue / 5);
    const yMax = yStep * 5;
    
    // 캔버스 클리어
    ctx.clearRect(0, 0, width, height);
    
    // 그리드 라인 그리기
    ctx.strokeStyle = '#f0f0f0';
    ctx.lineWidth = 1;
    
    // Y축 그리드
    for (let i = 0; i <= 5; i++) {
        const y = padding.top + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(width - padding.right, y);
        ctx.stroke();
        
        // Y축 레이블
        ctx.fillStyle = '#666';
        ctx.font = '12px Noto Sans KR';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillText(yMax - (yStep * i), padding.left - 10, y);
    }
    
    // X축 라인
    ctx.strokeStyle = '#333';
    ctx.beginPath();
    ctx.moveTo(padding.left, height - padding.bottom);
    ctx.lineTo(width - padding.right, height - padding.bottom);
    ctx.stroke();
    
    // Y축 라인
    ctx.beginPath();
    ctx.moveTo(padding.left, padding.top);
    ctx.lineTo(padding.left, height - padding.bottom);
    ctx.stroke();
    
    // 데이터 포인트 계산
    const xStep = chartWidth / (data.length - 1);
    const points = data.map((d, i) => ({
        x: padding.left + xStep * i,
        y: padding.top + chartHeight - (d.value / yMax) * chartHeight
    }));
    
    // 영역 채우기
    ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.beginPath();
    ctx.moveTo(points[0].x, height - padding.bottom);
    points.forEach(p => ctx.lineTo(p.x, p.y));
    ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
    ctx.closePath();
    ctx.fill();
    
    // 라인 그리기
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.beginPath();
    points.forEach((p, i) => {
        if (i === 0) ctx.moveTo(p.x, p.y);
        else ctx.lineTo(p.x, p.y);
    });
    ctx.stroke();
    
    // 포인트 그리기
    points.forEach((p, i) => {
        // 포인트 배경
        ctx.fillStyle = '#fff';
        ctx.beginPath();
        ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
        ctx.fill();
        
        // 포인트 테두리
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
        ctx.stroke();
        
        // X축 레이블
        ctx.fillStyle = '#666';
        ctx.font = '12px Noto Sans KR';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(data[i].label, p.x, height - padding.bottom + 10);
        
        // 값 표시
        if (data[i].value > 0) {
            ctx.fillStyle = '#000';
            ctx.font = 'bold 11px Noto Sans KR';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.fillText(data[i].value, p.x, p.y - 8);
        }
    });
}

// 페이지 로드 시 차트 그리기
window.addEventListener('load', drawLineChart);
window.addEventListener('resize', drawLineChart);
</script>

<?php require_once(__DIR__ . '/inc/footer.php'); ?>