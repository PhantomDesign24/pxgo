<?php
/*
 * 파일명: index.php
 * 위치: /admin/index.php
 * 기능: 관리자 대시보드
 * 작성일: 2025-01-31
 */

$page_title = '대시보드';
require_once(__DIR__ . '/inc/header.php');

// 데이터베이스 연결
require_once(__DIR__ . '/../db_config.php');

try {
    $pdo = getDB();
    
    // 오늘 날짜
    $today = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $todayStart = $today->format('Y-m-d 00:00:00');
    $todayEnd = $today->format('Y-m-d 23:59:59');
    
    // 이번달 시작일
    $monthStart = $today->format('Y-m-01 00:00:00');
    
    // 통계 데이터 조회
    // 1. 오늘 견적
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM computer_inquiries WHERE created_at BETWEEN :start AND :end");
    $stmt->execute([':start' => $todayStart, ':end' => $todayEnd]);
    $todayInquiries = $stmt->fetchColumn();
    
    // 2. 이번달 견적
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM computer_inquiries WHERE created_at >= :start");
    $stmt->execute([':start' => $monthStart]);
    $monthInquiries = $stmt->fetchColumn();
    
    // 3. 전체 견적
    $totalInquiries = $pdo->query("SELECT COUNT(*) FROM computer_inquiries")->fetchColumn();
    
    // 4. 대기중 견적
    $pendingInquiries = $pdo->query("SELECT COUNT(*) FROM computer_inquiries WHERE status = 'new'")->fetchColumn();
    
    // 5. 최근 견적 목록
    $recentInquiries = $pdo->query("
        SELECT id, name, device_type, status, created_at 
        FROM computer_inquiries 
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. 기기별 통계
    $deviceStats = $pdo->query("
        SELECT device_type, COUNT(*) as count 
        FROM computer_inquiries 
        GROUP BY device_type 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. 일별 견적 추이 (최근 7일)
    $dailyStats = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM computer_inquiries 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
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
    'new' => '견적 진행중',
    'processing' => '검수 대기',
    'completed' => '견적 완료',
    'cancelled' => '취소됨'
];
?>

<!-- ===================================
 * 페이지 헤더
 * ===================================
 -->
<div class="page-header">
    <h2 class="page-title">대시보드</h2>
    <p class="page-desc">픽셀창고 관리 시스템에 오신 것을 환영합니다.</p>
</div>

<!-- ===================================
 * 통계 카드
 * ===================================
 -->
<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="dashboard-card-icon">
            <i class="bi bi-calendar-day"></i>
        </div>
        <div class="dashboard-card-value"><?php echo number_format($todayInquiries); ?></div>
        <div class="dashboard-card-label">오늘 견적</div>
    </div>
    
    <div class="dashboard-card">
        <div class="dashboard-card-icon">
            <i class="bi bi-calendar-month"></i>
        </div>
        <div class="dashboard-card-value"><?php echo number_format($monthInquiries); ?></div>
        <div class="dashboard-card-label">이번달 견적</div>
    </div>
    
    <div class="dashboard-card">
        <div class="dashboard-card-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="dashboard-card-value"><?php echo number_format($pendingInquiries); ?></div>
        <div class="dashboard-card-label">대기중 견적</div>
    </div>
    
    <div class="dashboard-card">
        <div class="dashboard-card-icon">
            <i class="bi bi-archive"></i>
        </div>
        <div class="dashboard-card-value"><?php echo number_format($totalInquiries); ?></div>
        <div class="dashboard-card-label">전체 견적</div>
    </div>
</div>

<!-- ===================================
 * 차트 영역
 * ===================================
 -->
<div class="row">
    <div class="col-md-8">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">최근 7일 견적 추이</h3>
            </div>
            <canvas id="dailyChart" height="80"></canvas>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">기기별 통계</h3>
            </div>
            <canvas id="deviceChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- ===================================
 * 최근 견적
 * ===================================
 -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">최근 견적</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>고객명</th>
                        <th>기기종류</th>
                        <th>상태</th>
                        <th>접수시간</th>
                        <th>액션</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentInquiries as $inquiry): ?>
                    <tr>
                        <td>#<?php echo $inquiry['id']; ?></td>
                        <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                        <td><?php echo $deviceTypes[$inquiry['device_type']] ?? $inquiry['device_type']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $inquiry['status']; ?>">
                                <?php echo $statusList[$inquiry['status']] ?? $inquiry['status']; ?>
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
 * 빠른 링크
 * ===================================
 -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">빠른 링크</h3>
    </div>
    <div class="card-body">
        <div class="quick-links">
            <a href="inquiries.php?status=new" class="quick-link">
                <i class="bi bi-inbox"></i>
                <div>
                    <div class="font-weight-600">대기중 견적</div>
                    <small class="text-muted">처리 대기중인 견적 확인</small>
                </div>
            </a>
            
            <a href="price_management.php" class="quick-link">
                <i class="bi bi-currency-dollar"></i>
                <div>
                    <div class="font-weight-600">가격 관리</div>
                    <small class="text-muted">매입 가격 조정</small>
                </div>
            </a>
            
            <a href="../" target="_blank" class="quick-link">
                <i class="bi bi-house"></i>
                <div>
                    <div class="font-weight-600">사이트 바로가기</div>
                    <small class="text-muted">메인 사이트 확인</small>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- ===================================
 * 추가 스타일
 * ===================================
 -->
<style>
.row {
    display: flex;
    flex-wrap: wrap;
    margin: -10px;
}

.col-md-8 {
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
    padding: 10px;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
    padding: 10px;
}

@media (max-width: 768px) {
    .col-md-8,
    .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

.font-weight-600 {
    font-weight: 600;
}

.badge-new {
    background: #fff3cd;
    color: #856404;
}

.badge-processing {
    background: #cfe2ff;
    color: #052c65;
}

.badge-completed {
    background: #d1e7dd;
    color: #0a3622;
}

.badge-cancelled {
    background: #f8d7da;
    color: #58151c;
}
</style>

<!-- ===================================
 * Chart.js 대체 - Canvas로 직접 구현
 * ===================================
 -->
<script>
<!-- ===================================
 * Chart.js 대체 - Canvas로 직접 구현
 * ===================================
 -->
<script>
// 간단한 차트 구현 (Chart.js 없이)
function drawLineChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const padding = 40;
    const chartWidth = width - padding * 2;
    const chartHeight = height - padding * 2;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Find max value
    const maxValue = Math.max(...data) || 1;
    const step = Math.ceil(maxValue / 5);
    const maxY = step * 5;
    
    // Draw grid lines
    ctx.strokeStyle = '#e0e0e0';
    ctx.lineWidth = 1;
    
    for (let i = 0; i <= 5; i++) {
        const y = padding + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
        
        // Y axis labels
        ctx.fillStyle = '#666';
        ctx.font = '12px Noto Sans KR';
        ctx.textAlign = 'right';
        ctx.fillText(maxY - (maxY / 5) * i, padding - 10, y + 4);
    }
    
    // Draw data line
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.beginPath();
    
    const xStep = chartWidth / (labels.length - 1);
    
    data.forEach((value, index) => {
        const x = padding + xStep * index;
        const y = padding + chartHeight - (value / maxY) * chartHeight;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
        
        // Draw point
        ctx.fillStyle = '#000';
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fill();
        
        // X axis labels
        ctx.fillStyle = '#666';
        ctx.font = '12px Noto Sans KR';
        ctx.textAlign = 'center';
        ctx.fillText(labels[index], x, height - 15);
    });
    
    ctx.stroke();
}

function drawDoughnutChart(canvasId, labels, data, colors) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const centerX = width / 2;
    const centerY = height / 2 - 20;
    const radius = Math.min(width, height) / 3;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Calculate total
    const total = data.reduce((sum, val) => sum + val, 0);
    if (total === 0) return;
    
    // Draw segments
    let startAngle = -Math.PI / 2;
    
    data.forEach((value, index) => {
        const sliceAngle = (value / total) * 2 * Math.PI;
        
        // Draw slice
        ctx.fillStyle = colors[index % colors.length];
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.closePath();
        ctx.fill();
        
        // Draw border
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        startAngle += sliceAngle;
    });
    
    // Draw center circle (doughnut hole)
    ctx.fillStyle = '#fff';
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius * 0.6, 0, Math.PI * 2);
    ctx.fill();
    
    // Draw legend
    const legendY = height - 60;
    let legendX = 20;
    
    labels.forEach((label, index) => {
        // Color box
        ctx.fillStyle = colors[index % colors.length];
        ctx.fillRect(legendX, legendY, 12, 12);
        
        // Label
        ctx.fillStyle = '#666';
        ctx.font = '11px Noto Sans KR';
        ctx.textAlign = 'left';
        ctx.fillText(label + ' (' + data[index] + ')', legendX + 16, legendY + 10);
        
        legendX += ctx.measureText(label + ' (' + data[index] + ')').width + 30;
        
        // New line if needed
        if (legendX > width - 100) {
            legendX = 20;
            legendY += 20;
        }
    });
}

// 차트 데이터 준비
const dailyLabels = [];
const dailyData = [];
const dailyStatsMap = {};

<?php foreach ($dailyStats as $stat): ?>
dailyStatsMap['<?php echo $stat['date']; ?>'] = <?php echo $stat['count']; ?>;
<?php endforeach; ?>

// 최근 7일 모두 표시
for (let i = 6; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    const dateStr = date.toISOString().split('T')[0];
    const label = (date.getMonth() + 1) + '/' + date.getDate();
    
    dailyLabels.push(label);
    dailyData.push(dailyStatsMap[dateStr] || 0);
}

// 기기별 통계
const deviceLabels = [];
const deviceData = [];
const deviceColors = [
    '#000000', '#333333', '#666666', '#999999', '#cccccc',
    '#1a1a1a', '#4d4d4d', '#808080', '#b3b3b3'
];

<?php foreach ($deviceStats as $index => $stat): ?>
deviceLabels.push('<?php echo $deviceTypes[$stat['device_type']] ?? $stat['device_type']; ?>');
deviceData.push(<?php echo $stat['count']; ?>);
<?php endforeach; ?>

// 차트 그리기
window.onload = function() {
    drawLineChart('dailyChart', dailyLabels, dailyData);
    drawDoughnutChart('deviceChart', deviceLabels, deviceData, deviceColors);
    
    // 반응형 처리
    window.addEventListener('resize', function() {
        drawLineChart('dailyChart', dailyLabels, dailyData);
        drawDoughnutChart('deviceChart', deviceLabels, deviceData, deviceColors);
    });
};
</script>

<?php require_once(__DIR__ . '/inc/footer.php'); ?>