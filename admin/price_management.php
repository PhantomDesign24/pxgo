<?php
/*
 * 파일명: price_management.php
 * 위치: /admin/price_management.php
 * 기능: 매입 가격 관리 페이지 - 깔끔한 블랙앤화이트 디자인
 * 작성일: 2025-01-31
 */

$page_title = '가격 관리';
require_once(__DIR__ . '/inc/header.php');

// 데이터베이스 연결
require_once(__DIR__ . '/../db_config.php');
$pdo = getDB();

// 카테고리 목록 가져오기
$categories = $pdo->query("SELECT DISTINCT category_main FROM nm_products ORDER BY category_main")->fetchAll();
?>

<!-- ===================================
 * 페이지 헤더
 * ===================================
 -->
<div class="page-header">
    <h2 class="page-title">매입 가격 관리</h2>
    <p class="page-desc">나노메모리 제품의 매입 가격을 관리합니다.</p>
</div>

<!-- ===================================
 * 통계 카드
 * ===================================
 -->
<?php
$stats = $pdo->query("
    SELECT 
        COUNT(DISTINCT nm.id) as total_nm_products,
        COUNT(DISTINCT pp.id) as total_pp_products,
        COUNT(CASE WHEN pp.is_custom = 1 THEN 1 END) as custom_products,
        MAX(pp.updated_at) as last_update
    FROM nm_products nm
    LEFT JOIN purchase_prices pp ON nm.id = pp.nm_product_id
")->fetch();
?>

<div class="stats-grid mb-3">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        <div class="stat-value"><?php echo number_format($stats['total_nm_products']); ?></div>
        <div class="stat-label">나노메모리 제품</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
        <div class="stat-value"><?php echo number_format($stats['total_pp_products']); ?></div>
        <div class="stat-label">관리중인 제품</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
        <div class="stat-value"><?php echo number_format($stats['custom_products']); ?></div>
        <div class="stat-label">개별 수정</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
        <div class="stat-value"><?php echo $stats['last_update'] ? date('m/d H:i', strtotime($stats['last_update'])) : '-'; ?></div>
        <div class="stat-label">마지막 업데이트</div>
    </div>
</div>

<!-- ===================================
 * 일괄 조정
 * ===================================
 -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">가격 일괄 조정</h3>
    </div>
    <div class="card-body">
        <form id="bulkAdjustForm" onsubmit="return applyBulkAdjustment(event)">
            <div class="bulk-form-grid">
                <div class="form-group">
                    <label class="form-label">적용 범위</label>
                    <select class="form-select" id="adjustScope" onchange="updateScopeOptions()">
                        <option value="all">전체 제품</option>
                        <option value="category">카테고리별</option>
                    </select>
                </div>
                
                <div class="form-group" id="categorySelectDiv" style="display:none;">
                    <label class="form-label">카테고리</label>
                    <select class="form-select" id="adjustCategory">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_main']; ?>"><?php echo $cat['category_main']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">조정 방식</label>
                    <select class="form-select" id="adjustType">
                        <option value="percentage">퍼센트(%)</option>
                        <option value="fixed">정액(원)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">조정값</label>
                    <input type="number" class="form-control" id="adjustValue" step="0.01" required>
                    <small class="text-muted">음수=할인, 양수=할증</small>
                </div>
                
                <div class="form-group d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> 적용
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===================================
 * 필터 및 도구
 * ===================================
 -->
<div class="filter-bar mb-3">
    <div class="filter-left">
        <select class="form-select" id="filterCategory" onchange="loadProducts()">
            <option value="">전체 카테고리</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['category_main']; ?>"><?php echo $cat['category_main']; ?></option>
            <?php endforeach; ?>
        </select>
        
        <input type="text" class="form-control" id="searchProduct" placeholder="제품명 검색..." onkeyup="searchDelay()">
    </div>
    
    <div class="filter-right">
        <button class="btn btn-primary" onclick="syncFromNano()">
            <i class="bi bi-arrow-repeat"></i> 나노메모리 동기화
        </button>
    </div>
</div>

<!-- ===================================
 * 제품 목록
 * ===================================
 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="35%">제품명</th>
                        <th width="15%">카테고리</th>
                        <th width="12%" class="text-right">원본가격</th>
                        <th width="10%" class="text-center">조정</th>
                        <th width="13%" class="text-right">매입가격</th>
                        <th width="10%" class="text-center">관리</th>
                    </tr>
                </thead>
                <tbody id="productList">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner"></div> 로딩 중...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination-wrapper">
            <ul class="pagination" id="pagination"></ul>
        </div>
    </div>
</div>

<!-- ===================================
 * 개별 수정 모달
 * ===================================
 -->
<div class="modal" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">개별 가격 수정</h5>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId">
                    
                    <div class="form-group">
                        <label class="form-label">제품명</label>
                        <input type="text" class="form-control" id="editProductName" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">원본 가격</label>
                        <input type="text" class="form-control" id="editOriginalPrice" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">조정 방식</label>
                        <select class="form-select" id="editAdjustType" onchange="calculateEditPrice()">
                            <option value="percentage">퍼센트(%)</option>
                            <option value="fixed">정액(원)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">조정값</label>
                        <input type="number" class="form-control" id="editAdjustValue" step="0.01" onkeyup="calculateEditPrice()" onchange="calculateEditPrice()">
                        <small class="text-muted">음수=할인, 양수=할증</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">최종 매입가</label>
                        <input type="number" class="form-control" id="editFinalPrice" step="1000">
                        <small class="text-muted">직접 입력도 가능합니다</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">수정 사유</label>
                        <textarea class="form-control" id="editNote" rows="2"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="editActive" checked>
                        <label for="editActive">활성화</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">취소</button>
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
/* 통계 그리드 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-300);
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}

.stat-card:hover {
    border-color: var(--color-gray-700);
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 24px;
    color: var(--color-gray-600);
    margin-bottom: 10px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-black);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: var(--color-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* 일괄 조정 폼 */
.bulk-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

/* 필터 바 */
.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.filter-left {
    display: flex;
    gap: 12px;
    flex: 1;
}

.filter-left select {
    width: 200px;
}

.filter-left input {
    width: 300px;
}

/* 가격 테이블 */
.table td {
    vertical-align: middle;
}

.price-original {
    color: var(--color-gray-600);
    font-size: 13px;
}

.price-final {
    font-weight: 600;
    font-size: 15px;
    color: var(--color-black);
}

.adjustment-text {
    font-size: 12px;
    color: var(--color-gray-600);
}

.custom-mark {
    display: inline-block;
    padding: 2px 6px;
    background: var(--color-gray-200);
    border-radius: 3px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* 모달 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    width: 90%;
    max-width: 500px;
}

.modal-content {
    background: var(--color-white);
    border-radius: 4px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--color-gray-300);
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--color-gray-600);
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: var(--color-gray-100);
    color: var(--color-black);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--color-gray-300);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* 폼 체크박스 */
.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

/* 페이지네이션 */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination {
    display: flex;
    gap: 4px;
    list-style: none;
}

.pagination li {
    display: inline-block;
}

.pagination a {
    display: block;
    padding: 8px 12px;
    border: 1px solid var(--color-gray-300);
    border-radius: 4px;
    color: var(--color-gray-700);
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s;
}

.pagination a:hover {
    background: var(--color-gray-100);
    border-color: var(--color-gray-400);
}

.pagination .active a {
    background: var(--color-black);
    color: var(--color-white);
    border-color: var(--color-black);
}

/* 액션 버튼 */
.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-group {
    display: inline-flex;
    gap: 4px;
}
</style>

<!-- ===================================
 * 페이지 스크립트
 * ===================================
 -->
<script>
let currentPage = 1;
let searchTimer;

// ===================================
// 페이지 로드
// ===================================
window.onload = function() {
    loadProducts();
};

// ===================================
// 제품 목록 로드
// ===================================
async function loadProducts(page = 1) {
    currentPage = page;
    
    const params = new URLSearchParams({
        page: page,
        category: document.getElementById('filterCategory').value,
        search: document.getElementById('searchProduct').value
    });
    
    try {
        const tbody = document.getElementById('productList');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner"></div> 로딩 중...</td></tr>';
        
        const response = await fetch(`api/get_prices.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.products);
            displayPagination(data.totalPages, page);
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">오류가 발생했습니다.</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('productList').innerHTML = 
            '<tr><td colspan="7" class="text-center text-muted">서버 연결에 실패했습니다.</td></tr>';
    }
}

// ===================================
// 제품 표시
// ===================================
function displayProducts(products) {
    const tbody = document.getElementById('productList');
    tbody.innerHTML = '';
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">제품이 없습니다.</td></tr>';
        return;
    }
    
    products.forEach(product => {
        const row = tbody.insertRow();
        
        // 조정 표시
        let adjustmentText = '-';
        if (product.adjustment_value != 0) {
            const sign = product.adjustment_value > 0 ? '+' : '';
            if (product.adjustment_type === 'percentage') {
                adjustmentText = `${sign}${product.adjustment_value}%`;
            } else {
                adjustmentText = `${sign}${numberFormat(product.adjustment_value)}원`;
            }
        }
        
        row.innerHTML = `
            <td>${product.id}</td>
            <td>
                <div>${product.product_name}</div>
                <small class="text-muted">${product.classification}</small>
            </td>
            <td>${product.category_main}</td>
            <td class="text-right">
                <span class="price-original">${numberFormat(product.original_price)}원</span>
            </td>
            <td class="text-center">
                <span class="adjustment-text">${adjustmentText}</span>
            </td>
            <td class="text-right">
                <span class="price-final">${numberFormat(product.final_price)}원</span>
                ${product.is_custom == 1 ? '<span class="custom-mark">수정</span>' : ''}
            </td>
            <td class="text-center">
                <div class="btn-group">
                    <button class="btn btn-secondary btn-sm btn-icon" onclick="editProduct(${product.id})" title="가격 수정">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn ${product.is_active ? 'btn-primary' : 'btn-secondary'} btn-sm btn-icon" 
                            onclick="toggleActive(${product.id}, ${product.is_active})" 
                            title="${product.is_active ? '활성 상태 - 클릭하여 비활성화' : '비활성 상태 - 클릭하여 활성화'}">
                        <i class="bi ${product.is_active ? 'bi-check-circle-fill' : 'bi-x-circle'}"></i>
                    </button>
                </div>
            </td>
        `;
    });
}

// ===================================
// 일괄 조정
// ===================================
async function applyBulkAdjustment(event) {
    event.preventDefault();
    
    if (!confirm('정말 일괄 조정하시겠습니까? 개별 수정된 제품은 제외됩니다.')) {
        return false;
    }
    
    const data = {
        scope: document.getElementById('adjustScope').value,
        category: document.getElementById('adjustCategory').value,
        type: document.getElementById('adjustType').value,
        value: parseFloat(document.getElementById('adjustValue').value)
    };
    
    if (isNaN(data.value) || data.value === 0) {
        alert('조정값을 입력해주세요.');
        return false;
    }
    
    try {
        const response = await fetch('api/bulk_adjust_prices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            loadProducts(currentPage);
            document.getElementById('bulkAdjustForm').reset();
        } else {
            alert('오류: ' + (result.error || '처리에 실패했습니다.'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('처리 중 오류가 발생했습니다.');
    }
    
    return false;
}

// ===================================
// 개별 수정
// ===================================
async function editProduct(id) {
    try {
        const response = await fetch(`api/get_price.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const product = data.product;
            
            document.getElementById('editId').value = product.id;
            document.getElementById('editProductName').value = product.product_name;
            document.getElementById('editOriginalPrice').value = numberFormat(product.original_price) + '원';
            document.getElementById('editAdjustType').value = product.adjustment_type || 'percentage';
            document.getElementById('editAdjustValue').value = product.adjustment_value || 0;
            document.getElementById('editFinalPrice').value = product.final_price;
            document.getElementById('editNote').value = product.custom_note || '';
            document.getElementById('editActive').checked = product.is_active == 1;
            
            document.getElementById('editModal').classList.add('show');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('제품 정보를 불러올 수 없습니다.');
    }
}

function calculateEditPrice() {
    const originalPrice = parseInt(document.getElementById('editOriginalPrice').value.replace(/[^0-9]/g, ''));
    const type = document.getElementById('editAdjustType').value;
    const value = parseFloat(document.getElementById('editAdjustValue').value) || 0;
    
    let finalPrice;
    if (type === 'percentage') {
        finalPrice = originalPrice + (originalPrice * value / 100);
    } else {
        finalPrice = originalPrice + value;
    }
    
    // 1000원 단위 반올림
    finalPrice = Math.round(finalPrice / 1000) * 1000;
    
    document.getElementById('editFinalPrice').value = finalPrice;
}

async function saveEdit() {
    const data = {
        id: document.getElementById('editId').value,
        adjustment_type: document.getElementById('editAdjustType').value,
        adjustment_value: parseFloat(document.getElementById('editAdjustValue').value),
        final_price: parseInt(document.getElementById('editFinalPrice').value),
        custom_note: document.getElementById('editNote').value,
        is_active: document.getElementById('editActive').checked ? 1 : 0
    };
    
    try {
        const response = await fetch('api/update_price.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadProducts(currentPage);
        } else {
            alert('오류: ' + (result.error || '저장에 실패했습니다.'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('저장 중 오류가 발생했습니다.');
    }
}

function closeModal() {
    document.getElementById('editModal').classList.remove('show');
}

// ===================================
// 활성화 토글
// ===================================
async function toggleActive(id, currentStatus) {
    try {
        const response = await fetch('api/toggle_active.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, is_active: currentStatus ? 0 : 1 })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            loadProducts(currentPage);
        } else {
            alert('오류: ' + (result.error || '상태 변경에 실패했습니다.'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('상태 변경 중 오류가 발생했습니다.');
    }
}

// ===================================
// 동기화
// ===================================
async function syncFromNano() {
    if (!confirm('나노메모리 데이터를 동기화하시겠습니까?\n신규 제품이 추가되고 가격이 업데이트됩니다.')) {
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> 동기화 중...';
    
    try {
        const response = await fetch('api/sync_prices.php', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            loadProducts(1);
        } else {
            alert('오류: ' + (result.error || '동기화에 실패했습니다.'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('동기화 중 오류가 발생했습니다.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> 나노메모리 동기화';
    }
}

// ===================================
// 유틸리티
// ===================================
function updateScopeOptions() {
    const scope = document.getElementById('adjustScope').value;
    document.getElementById('categorySelectDiv').style.display = 
        scope === 'category' ? 'block' : 'none';
}

function searchDelay() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadProducts(1), 300);
}

function numberFormat(num) {
    return new Intl.NumberFormat('ko-KR').format(num);
}

function displayPagination(totalPages, currentPage) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous
    if (currentPage > 1) {
        const li = document.createElement('li');
        li.innerHTML = `<a href="#" onclick="loadProducts(${currentPage - 1}); return false;">이전</a>`;
        pagination.appendChild(li);
    }
    
    // Page numbers
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        const li = document.createElement('li');
        if (i === currentPage) {
            li.className = 'active';
        }
        li.innerHTML = `<a href="#" onclick="loadProducts(${i}); return false;">${i}</a>`;
        pagination.appendChild(li);
    }
    
    // Next
    if (currentPage < totalPages) {
        const li = document.createElement('li');
        li.innerHTML = `<a href="#" onclick="loadProducts(${currentPage + 1}); return false;">다음</a>`;
        pagination.appendChild(li);
    }
}

// 모달 외부 클릭 시 닫기
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once(__DIR__ . '/inc/footer.php'); ?>