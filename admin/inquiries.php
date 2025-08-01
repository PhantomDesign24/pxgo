<?php
/*
 * 파일명: inquiries.php
 * 위치: /admin/inquiries.php
 * 기능: 견적 관리 페이지 - 깔끔한 블랙앤화이트 디자인
 * 작성일: 2025-01-31
 */

$page_title = '견적 관리';
require_once(__DIR__ . '/inc/header.php');

// 데이터베이스 연결
require_once(__DIR__ . '/../db_config.php');

// 페이지네이션
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 필터
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_device = isset($_GET['device']) ? $_GET['device'] : '';
$filter_test = isset($_GET['test']) ? $_GET['test'] : '';

try {
    $pdo = getDB();
    
    // 전체 개수 조회
    $countSql = "SELECT COUNT(*) FROM computer_inquiries WHERE 1=1";
    $params = [];
    
    if ($filter_status) {
        $countSql .= " AND status = :status";
        $params[':status'] = $filter_status;
    }
    if ($filter_device) {
        $countSql .= " AND device_type = :device";
        $params[':device'] = $filter_device;
    }
    if ($filter_test !== '') {
        $countSql .= " AND is_test_data = :test";
        $params[':test'] = $filter_test;
    }
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    
    // 데이터 조회
    $sql = "SELECT * FROM computer_inquiries WHERE 1=1";
    if ($filter_status) {
        $sql .= " AND status = :status";
    }
    if ($filter_device) {
        $sql .= " AND device_type = :device";
    }
    if ($filter_test !== '') {
        $sql .= " AND is_test_data = :test";
    }
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('데이터베이스 오류: ' . $e->getMessage());
}

// 기기 종류 목록
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

// 상태 목록
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
    <h2 class="page-title">견적 관리</h2>
    <p class="page-desc">고객이 요청한 견적을 관리합니다.</p>
</div>

<!-- ===================================
 * 필터
 * ===================================
 -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="filter-form">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">전체</option>
                        <?php foreach ($statusList as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $filter_status === $key ? 'selected' : '' ?>><?= $value ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">기기 종류</label>
                    <select name="device" class="form-select" onchange="this.form.submit()">
                        <option value="">전체</option>
                        <?php foreach ($deviceTypes as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $filter_device === $key ? 'selected' : '' ?>><?= $value ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">데이터 종류</label>
                    <select name="test" class="form-select" onchange="this.form.submit()">
                        <option value="">전체</option>
                        <option value="0" <?= $filter_test === '0' ? 'selected' : '' ?>>실제 데이터</option>
                        <option value="1" <?= $filter_test === '1' ? 'selected' : '' ?>>테스트 데이터</option>
                    </select>
                </div>
                
                <div class="form-group d-flex align-items-end">
                    <a href="inquiries.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> 초기화
                    </a>
                </div>
            </div>
            
            <div class="filter-info">
                전체 <?= number_format($totalCount) ?>건
            </div>
        </form>
    </div>
</div>

<!-- ===================================
 * 일괄 작업
 * ===================================
 -->
<div class="bulk-actions mb-3" id="bulkActions" style="display: none;">
    <div class="bulk-actions-inner">
        <span class="selected-count">
            <i class="bi bi-check2-square"></i>
            <span id="selectedCount">0</span>개 선택됨
        </span>
        
        <div class="bulk-actions-buttons">
            <select id="bulkStatus" class="form-select">
                <option value="">상태 변경...</option>
                <?php foreach ($statusList as $key => $value): ?>
                    <option value="<?= $key ?>"><?= $value ?></option>
                <?php endforeach; ?>
            </select>
            
            <button class="btn btn-primary btn-sm" onclick="bulkUpdateStatus()">
                <i class="bi bi-check-circle"></i> 상태 변경
            </button>
            
            <button class="btn btn-secondary btn-sm" onclick="bulkDelete()">
                <i class="bi bi-trash"></i> 선택 삭제
            </button>
            
            <button class="btn btn-secondary btn-sm" onclick="clearSelection()">
                <i class="bi bi-x"></i> 선택 해제
            </button>
        </div>
    </div>
</div>

<!-- ===================================
 * 견적 목록
 * ===================================
 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="30">
                            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                        </th>
                        <th width="60">ID</th>
                        <th width="100">이름</th>
                        <th width="120">연락처</th>
                        <th width="120">기기종류</th>
                        <th>모델</th>
                        <th width="60">개수</th>
                        <th width="100">상태</th>
                        <th width="100">매입방식</th>
                        <th width="100">접수일시</th>
                        <th width="100">액션</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="inquiry-checkbox" value="<?= $inquiry['id'] ?>" onchange="updateSelectedCount()">
                        </td>
                        <td>
                            #<?= $inquiry['id'] ?>
                            <?php if ($inquiry['is_test_data']): ?>
                                <span class="badge badge-secondary">TEST</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($inquiry['name']) ?></td>
                        <td>
                            <a href="tel:<?= $inquiry['phone'] ?>" class="text-primary">
                                <?= htmlspecialchars($inquiry['phone']) ?>
                            </a>
                        </td>
                        <td><?= $deviceTypes[$inquiry['device_type']] ?? $inquiry['device_type'] ?></td>
                        <td>
                            <?= htmlspecialchars($inquiry['model'] ?: '-') ?>
                            <?php if ($inquiry['is_company']): ?>
                                <span class="badge badge-primary">기업</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $inquiry['quantity'] ?: 1 ?>개</td>
                        <td>
                            <span class="status-badge status-<?= $inquiry['status'] ?>">
                                <?= $statusList[$inquiry['status']] ?? $inquiry['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?= $inquiry['service_type'] === 'delivery' ? '택배' : '출장' ?>
                            <?php if ($inquiry['location']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($inquiry['location']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('m/d H:i', strtotime($inquiry['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-secondary btn-sm btn-icon" onclick="viewInquiry(<?= $inquiry['id'] ?>)" title="상세">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm btn-icon" onclick="editInquiry(<?= $inquiry['id'] ?>)" title="수정">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm btn-icon" onclick="deleteInquiry(<?= $inquiry['id'] ?>)" title="삭제">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 페이지네이션 -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li <?= $i === $page ? 'class="active"' : '' ?>>
                        <a href="?page=<?= $i ?>&status=<?= $filter_status ?>&device=<?= $filter_device ?>&test=<?= $filter_test ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===================================
 * 상세 보기 모달
 * ===================================
 -->
<div class="modal" id="viewModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">견적 상세 정보</h5>
                <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- 동적 로드 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 수정 모달
 * ===================================
 -->
<div class="modal" id="editModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">견적 수정</h5>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId" name="id">
                    
                    <div class="form-group">
                        <label class="form-label">상태</label>
                        <select id="editStatus" name="status" class="form-select">
                            <?php foreach ($statusList as $key => $value): ?>
                                <option value="<?= $key ?>"><?= $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">견적가 (만원)</label>
                        <input type="number" id="editPrice" name="estimated_price" class="form-control" placeholder="예: 150">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">메모</label>
                        <textarea id="editMessage" name="message" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">취소</button>
                <button type="button" class="btn btn-primary" onclick="saveEdit()">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 추가 스타일
 * ===================================
 -->
<style>
/* 필터 폼 */
.filter-form {
    position: relative;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.filter-info {
    position: absolute;
    top: 0;
    right: 0;
    font-size: 14px;
    color: var(--color-gray-600);
    font-weight: 500;
}

/* 일괄 작업 */
.bulk-actions {
    background: var(--color-gray-100);
    border: 1px solid var(--color-gray-300);
    border-radius: 4px;
    padding: 16px;
}

.bulk-actions-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.selected-count {
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bulk-actions-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.bulk-actions-buttons select {
    width: 180px;
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

/* 배지 */
.badge-primary {
    background: var(--color-black);
    color: var(--color-white);
}

/* 모달 크기 */
.modal-lg {
    max-width: 800px;
}

/* 상세 정보 테이블 */
.detail-table {
    width: 100%;
}

.detail-table th {
    width: 150px;
    padding: 10px;
    background: var(--color-gray-100);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-table td {
    padding: 10px;
}

/* 모바일 반응형 */
@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .bulk-actions-inner {
        flex-direction: column;
        align-items: stretch;
    }
    
    .bulk-actions-buttons {
        flex-wrap: wrap;
    }
    
    .table {
        font-size: 12px;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 2px;
    }
}
</style>

<!-- ===================================
 * 페이지 스크립트
 * ===================================
 -->
<script>
// 견적 데이터 저장
const inquiryData = <?= json_encode($inquiries) ?>;

// 체크박스 전체 선택/해제
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.inquiry-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedCount();
}

// 선택된 개수 업데이트
function updateSelectedCount() {
    const checked = document.querySelectorAll('.inquiry-checkbox:checked');
    const count = checked.length;
    
    document.getElementById('selectedCount').textContent = count;
    
    if (count > 0) {
        document.getElementById('bulkActions').style.display = 'block';
    } else {
        document.getElementById('bulkActions').style.display = 'none';
    }
}

// 선택 해제
function clearSelection() {
    document.getElementById('selectAll').checked = false;
    toggleAllCheckboxes();
}

// 일괄 상태 변경
async function bulkUpdateStatus() {
    const status = document.getElementById('bulkStatus').value;
    if (!status) {
        alert('변경할 상태를 선택해주세요.');
        return;
    }
    
    const checked = document.querySelectorAll('.inquiry-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('선택된 항목이 없습니다.');
        return;
    }
    
    if (!confirm(`${ids.length}개의 견적 상태를 변경하시겠습니까?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('status', status);
    ids.forEach(id => formData.append('ids[]', id));
    
    try {
        const response = await fetch('bulk_update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message || '오류가 발생했습니다.');
        }
    } catch (error) {
        alert('서버 오류가 발생했습니다.');
    }
}

// 일괄 삭제
async function bulkDelete() {
    const checked = document.querySelectorAll('.inquiry-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('선택된 항목이 없습니다.');
        return;
    }
    
    if (!confirm(`${ids.length}개의 견적을 삭제하시겠습니까?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    ids.forEach(id => formData.append('ids[]', id));
    
    try {
        const response = await fetch('bulk_update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message || '오류가 발생했습니다.');
        }
    } catch (error) {
        alert('서버 오류가 발생했습니다.');
    }
}

// 상세 보기
function viewInquiry(id) {
    const inquiry = inquiryData.find(item => item.id == id);
    if (!inquiry) return;
    
    // 상태 텍스트
    const statusText = {
        'new': '견적 진행중',
        'processing': '검수 대기',
        'completed': '견적 완료',
        'cancelled': '취소됨'
    };
    
    // 상태 텍스트
    const conditionText = {
        'excellent': '매우 좋음',
        'good': '좋음',
        'fair': '보통',
        'poor': '나쁨/고장'
    };
    
    // 기기 종류 텍스트
    const deviceText = {
        'pc_parts': 'PC부품',
        'pc_desktop': 'PC데스크탑',
        'pc_set': 'PC+모니터',
        'monitor': '모니터',
        'notebook': '노트북',
        'macbook': '맥북',
        'tablet': '태블릿',
        'nintendo': '닌텐도스위치',
        'applewatch': '애플워치'
    };
    
    // 한국 시간대 설정
    const createdDate = new Date(inquiry.created_at);
    const formattedDate = createdDate.toLocaleString('ko-KR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
    
    const html = `
        <table class="detail-table">
            <tr>
                <th>ID</th>
                <td>#${inquiry.id} ${inquiry.is_test_data ? '<span class="badge badge-secondary">TEST</span>' : ''}</td>
            </tr>
            <tr>
                <th>상태</th>
                <td><span class="status-badge status-${inquiry.status}">${statusText[inquiry.status] || inquiry.status}</span></td>
            </tr>
            <tr>
                <th>이름</th>
                <td>${inquiry.name} ${inquiry.is_company ? '<span class="badge badge-primary">기업</span>' : ''}</td>
            </tr>
            <tr>
                <th>연락처</th>
                <td><a href="tel:${inquiry.phone}" class="text-primary">${inquiry.phone}</a></td>
            </tr>
            <tr>
                <th>이메일</th>
                <td>${inquiry.email || '-'}</td>
            </tr>
            <tr>
                <th>기기 종류</th>
                <td>${deviceText[inquiry.device_type] || inquiry.device_type}</td>
            </tr>
            <tr>
                <th>브랜드</th>
                <td>${inquiry.brand || '-'}</td>
            </tr>
            <tr>
                <th>모델</th>
                <td>${inquiry.model || '-'}</td>
            </tr>
            <tr>
                <th>구매 연도</th>
                <td>${inquiry.purchase_year ? inquiry.purchase_year + '년' : '-'}</td>
            </tr>
            <tr>
                <th>제품 상태</th>
                <td>${conditionText[inquiry.condition_status] || inquiry.condition_status}</td>
            </tr>
            <tr>
                <th>개수</th>
                <td>${inquiry.quantity || 1}개</td>
            </tr>
            <tr>
                <th>매입 방식</th>
                <td>${inquiry.service_type === 'delivery' ? '무료 택배' : '당일 출장'}</td>
            </tr>
            ${inquiry.location ? `
            <tr>
                <th>지역</th>
                <td>${inquiry.location}</td>
            </tr>
            ` : ''}
            ${inquiry.estimated_price ? `
            <tr>
                <th>견적가</th>
                <td><strong>${inquiry.estimated_price}만원</strong></td>
            </tr>
            ` : ''}
            <tr>
                <th>메시지</th>
                <td>${inquiry.message ? inquiry.message.replace(/\n/g, '<br>') : '-'}</td>
            </tr>
            <tr>
                <th>접수일시</th>
                <td>${formattedDate}</td>
            </tr>
            ${inquiry.ip_address ? `
            <tr>
                <th>IP 주소</th>
                <td><small class="text-muted">${inquiry.ip_address}</small></td>
            </tr>
            ` : ''}
        </table>
    `;
    
    document.getElementById('viewModalBody').innerHTML = html;
    document.getElementById('viewModal').classList.add('show');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('show');
}

// 수정
function editInquiry(id) {
    const inquiry = inquiryData.find(item => item.id == id);
    if (!inquiry) return;
    
    document.getElementById('editId').value = inquiry.id;
    document.getElementById('editStatus').value = inquiry.status;
    document.getElementById('editPrice').value = inquiry.estimated_price || '';
    document.getElementById('editMessage').value = inquiry.message || '';
    
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

async function saveEdit() {
    const formData = new FormData(document.getElementById('editForm'));
    
    try {
        const response = await fetch('update_inquiry.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('수정되었습니다.');
            location.reload();
        } else {
            alert(result.message || '오류가 발생했습니다.');
        }
    } catch (error) {
        alert('서버 오류가 발생했습니다.');
    }
}

// 삭제
async function deleteInquiry(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    try {
        const response = await fetch('delete_inquiry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('삭제되었습니다.');
            location.reload();
        } else {
            alert(result.message || '오류가 발생했습니다.');
        }
    } catch (error) {
        alert('서버 오류가 발생했습니다.');
    }
}

// 모달 외부 클릭 시 닫기
document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php require_once(__DIR__ . '/inc/footer.php'); ?>