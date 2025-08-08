/*
 * 파일명: auto_quote.js
 * 위치: /
 * 기능: 자동 견적 시스템 JavaScript
 * 작성일: 2025-08-01
 * 수정일: 2025-08-02
 */

// ===================================
// 전역 변수
// ===================================
let currentCategory = '';
let allProducts = [];
let filteredProducts = [];
let selectedProducts = new Map();
let subCategories = new Set();
let classifications = new Set();
let selectedSubCategory = '';
let selectedClassification = '';

// ===================================
// 정렬 유틸리티 함수
// ===================================
// 한글/영어 정렬 함수
function koreanFirstSort(a, b) {
    const isKoreanA = /[ㄱ-ㅎ|ㅏ-ㅣ|가-힣]/.test(a);
    const isKoreanB = /[ㄱ-ㅎ|ㅏ-ㅣ|가-힣]/.test(b);
    
    if (isKoreanA && !isKoreanB) return -1;
    if (!isKoreanA && isKoreanB) return 1;
    
    return a.localeCompare(b, 'ko');
}

// 제품 정렬 함수 (한글 우선, 가격 높은 순)
function sortProducts(products) {
    return products.sort((a, b) => {
        // 같은 분류 내에서 가격 높은 순으로 정렬
        return b.final_price - a.final_price;
    });
}

// ===================================
// 초기화
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    // 첫 번째 카테고리 로드
    const firstTab = document.querySelector('.category-tab');
    if (firstTab) {
        const category = firstTab.textContent.trim();
        switchCategory(category);
    }
});

// ===================================
// 카테고리 전환
// ===================================
async function switchCategory(category) {
    currentCategory = category;
    
    // 탭 활성화 상태 변경
    document.querySelectorAll('.category-tab').forEach(tab => {
        if (tab.textContent.trim() === category) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
    
    // 필터 초기화
    resetFilters();
    
    // 제품 목록 로드
    await loadProducts(category);
}

// ===================================
// 제품 목록 로드
// ===================================
async function loadProducts(category) {
    const productList = document.getElementById('productList');
    productList.innerHTML = '<div class="loading"><i class="bi bi-hourglass-split"></i><p>제품 목록을 불러오는 중...</p></div>';
    
    try {
        const response = await fetch(`api/get_products.php?category=${encodeURIComponent(category)}`);
        const data = await response.json();
        
        if (data.success) {
            allProducts = data.products;
            filteredProducts = [...allProducts];
            
            // 서브카테고리와 분류 추출
            extractFilterOptions();
            
            // 필터 그리드 업데이트
            updateFilterGrid();
            
            // 제품 표시
            displayProducts(filteredProducts);
            updateProductCount();
        } else {
            productList.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-circle"></i><p>제품을 불러올 수 없습니다</p></div>';
        }
    } catch (error) {
        console.error('Error loading products:', error);
        productList.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-circle"></i><p>제품을 불러올 수 없습니다</p></div>';
    }
}

// ===================================
// 필터 옵션 추출
// ===================================
function extractFilterOptions() {
    // 초기화
    subCategories = new Set();
    classifications = new Set();
    
    // 모든 제품에서 소분류 추출
    allProducts.forEach(product => {
        if (product.category_sub) {
            subCategories.add(product.category_sub);
        }
    });
    
    console.log('All subcategories:', Array.from(subCategories));
    console.log('Sample product:', allProducts[0]);
}

// ===================================
// 필터 그리드 업데이트
// ===================================
function updateFilterGrid() {
    const filterSection = document.querySelector('.filter-section');
    
    let html = '';
    
    // 소분류 그리드
    if (subCategories.size > 0) {
        html += `
            <div class="filter-group">
                <h3 class="filter-title">소분류</h3>
                <div class="filter-grid">
                    <button class="filter-chip ${selectedSubCategory === '' ? 'active' : ''}" 
                            onclick="selectSubCategory('')">
                        전체
                    </button>
        `;
        
        // 한글 우선 정렬 적용
        Array.from(subCategories).sort(koreanFirstSort).forEach(subCategory => {
            // 이스케이프 처리
            const escapedSubCategory = subCategory.replace(/'/g, "\\'");
            html += `
                <button class="filter-chip ${selectedSubCategory === subCategory ? 'active' : ''}" 
                        onclick="selectSubCategory('${escapedSubCategory}')">
                    ${subCategory}
                </button>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    // 분류 그리드 - 소분류가 선택된 경우에만 표시
    if (selectedSubCategory && selectedSubCategory !== '' && classifications.size > 0) {
        html += `
            <div class="filter-group">
                <h3 class="filter-title">분류</h3>
                <div class="filter-grid">
                    <button class="filter-chip ${selectedClassification === '' ? 'active' : ''}" 
                            onclick="selectClassification('')">
                        전체
                    </button>
        `;
        
        // 한글 우선 정렬 적용
        Array.from(classifications).sort(koreanFirstSort).forEach(classification => {
            // 이스케이프 처리
            const escapedClassification = classification.replace(/'/g, "\\'");
            html += `
                <button class="filter-chip ${selectedClassification === classification ? 'active' : ''}" 
                        onclick="selectClassification('${escapedClassification}')">
                    ${classification}
                </button>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    } else if (!selectedSubCategory && subCategories.size > 0) {
        // 소분류가 선택되지 않은 경우 안내 메시지
        html += `
            <div class="filter-help">
                <i class="bi bi-info-circle"></i>
                <span>소분류를 선택하면 분류별로 더 세부적인 필터링이 가능합니다</span>
            </div>
        `;
    }
    
    filterSection.innerHTML = html;
}

// ===================================
// 소분류 선택
// ===================================
function selectSubCategory(subCategory) {
    console.log('selectSubCategory called with:', subCategory);
    
    selectedSubCategory = subCategory;
    
    // 소분류 변경 시 분류 초기화
    selectedClassification = '';
    
    // 분류 Set 초기화
    classifications = new Set();
    
    // 선택된 소분류에 해당하는 분류만 추출
    if (subCategory && subCategory !== '') {
        allProducts.forEach(product => {
            if (product.category_sub === subCategory && product.classification) {
                classifications.add(product.classification);
            }
        });
    }
    
    console.log('Classifications after selection:', Array.from(classifications));
    
    // UI 업데이트
    updateFilterGrid();
    applyFilters();
}

// ===================================
// 분류 선택
// ===================================
function selectClassification(classification) {
    selectedClassification = classification;
    updateFilterGrid();
    applyFilters();
}

// ===================================
// 필터링 함수
// ===================================
function applyFilters() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase().trim();
    
    filteredProducts = allProducts.filter(product => {
        // 소분류 필터
        if (selectedSubCategory && product.category_sub !== selectedSubCategory) {
            return false;
        }
        
        // 분류 필터
        if (selectedClassification && product.classification !== selectedClassification) {
            return false;
        }
        
        // 검색어 필터
        if (searchTerm) {
            const productName = product.product_name.toLowerCase();
            const productClassification = product.classification.toLowerCase();
            const productCategorySub = product.category_sub.toLowerCase();
            
            if (!productName.includes(searchTerm) && 
                !productClassification.includes(searchTerm) && 
                !productCategorySub.includes(searchTerm)) {
                return false;
            }
        }
        
        return true;
    });
    
    displayProducts(filteredProducts);
    updateProductCount();
}

// ===================================
// 제품 검색
// ===================================
function searchProducts() {
    const searchInput = document.getElementById('productSearch');
    const clearButton = document.querySelector('.search-clear');
    
    // 검색어가 있으면 X 버튼 표시
    if (searchInput.value.trim()) {
        clearButton.style.display = 'block';
    } else {
        clearButton.style.display = 'none';
    }
    
    applyFilters();
}

// ===================================
// 검색어 초기화
// ===================================
function clearSearch() {
    document.getElementById('productSearch').value = '';
    document.querySelector('.search-clear').style.display = 'none';
    applyFilters();
}

// ===================================
// 필터 초기화
// ===================================
function resetFilters() {
    selectedSubCategory = '';
    selectedClassification = '';
    document.getElementById('productSearch').value = '';
    document.querySelector('.search-clear').style.display = 'none';
}

// ===================================
// 제품 개수 업데이트
// ===================================
function updateProductCount() {
    const countElement = document.getElementById('productCount');
    const count = filteredProducts.length;
    
    if (count === 0) {
        countElement.textContent = '검색 결과가 없습니다';
        countElement.style.color = '#ef4444';
    } else {
        countElement.textContent = `${count}개의 제품`;
        countElement.style.color = '#6b7280';
    }
}

// ===================================
// 제품 표시
// ===================================
function displayProducts(products) {
    const productList = document.getElementById('productList');
    
    if (products.length === 0) {
        productList.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>검색 결과가 없습니다</p><span>다른 필터를 선택해보세요</span></div>';
        return;
    }
    
    // 분류별로 그룹화
    const groupedProducts = {};
    products.forEach(product => {
        const key = product.classification;
        if (!groupedProducts[key]) {
            groupedProducts[key] = [];
        }
        groupedProducts[key].push(product);
    });
    
    let html = '';
    
    // 각 분류별로 표시 (한글 우선 정렬)
    Object.keys(groupedProducts).sort(koreanFirstSort).forEach(classification => {
        // 분류 헤더 추가
        html += `
            <div class="classification-header">
                <i class="bi bi-folder2"></i>
                <span>${classification}</span>
                <small>(${groupedProducts[classification].length}개)</small>
            </div>
        `;
        
        // 해당 분류의 제품들을 가격 높은 순으로 정렬 후 표시
        const sortedProducts = sortProducts(groupedProducts[classification]);
        
        sortedProducts.forEach(product => {
            const isSelected = selectedProducts.has(product.id);
            
            html += `
                <div class="product-item ${isSelected ? 'selected' : ''}" 
                     onclick="toggleProduct(${product.id})"
                     data-product-id="${product.id}">
                    <div class="product-info">
                        <div class="product-category">${product.category_sub}</div>
                        <div class="product-name">${product.product_name}</div>
                    </div>
                    <div class="product-price">
                        <div class="price-label">매입가</div>
                        <div class="price-value">${numberFormat(product.final_price)}원</div>
                    </div>
                </div>
            `;
        });
    });
    
    productList.innerHTML = html;
}

// ===================================
// 제품 선택/해제
// ===================================
function toggleProduct(productId) {
    const product = allProducts.find(p => p.id === productId);
    if (!product) return;
    
    const productElement = document.querySelector(`[data-product-id="${productId}"]`);
    
    if (selectedProducts.has(productId)) {
        // 선택 해제
        selectedProducts.delete(productId);
        productElement.classList.remove('selected');
    } else {
        // 선택
        selectedProducts.set(productId, product);
        productElement.classList.add('selected');
    }
    
    updateSelectedList();
    updateQuote();
}

// ===================================
// 선택된 제품 목록 업데이트
// ===================================
function updateSelectedList() {
    const selectedProductsDiv = document.getElementById('selectedProducts');
    
    if (selectedProducts.size === 0) {
        selectedProductsDiv.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <p>선택된 제품이 없습니다</p>
                <span>왼쪽에서 제품을 선택해주세요</span>
            </div>
        `;
        document.querySelector('.btn-inquiry').disabled = true;
        return;
    }
    
    // 선택된 제품들을 배열로 변환하고 정렬
    const selectedProductsArray = Array.from(selectedProducts.values());
    
    // 분류별로 그룹화
    const groupedSelectedProducts = {};
    selectedProductsArray.forEach(product => {
        const key = product.classification;
        if (!groupedSelectedProducts[key]) {
            groupedSelectedProducts[key] = [];
        }
        groupedSelectedProducts[key].push(product);
    });
    
    let html = '';
    
    // 분류별로 정렬하여 표시
    Object.keys(groupedSelectedProducts).sort(koreanFirstSort).forEach(classification => {
        // 각 분류 내에서 가격 높은 순으로 정렬
        const sortedProducts = sortProducts(groupedSelectedProducts[classification]);
        
        sortedProducts.forEach(product => {
            html += `
                <div class="selected-item">
                    <div class="selected-item-info">
                        <div class="selected-item-category">${product.classification} | ${product.category_sub}</div>
                        <div class="selected-item-name">${product.product_name}</div>
                    </div>
                    <span class="selected-item-price">${numberFormat(product.final_price)}원</span>
                    <button class="btn-remove" onclick="removeProduct(${product.id})">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
        });
    });
    
    selectedProductsDiv.innerHTML = html;
    document.querySelector('.btn-inquiry').disabled = false;
}

// ===================================
// 제품 제거
// ===================================
function removeProduct(productId) {
    selectedProducts.delete(productId);
    
    // 제품 목록에서 선택 상태 해제
    const productElement = document.querySelector(`[data-product-id="${productId}"]`);
    if (productElement) {
        productElement.classList.remove('selected');
    }
    
    updateSelectedList();
    updateQuote();
}

// ===================================
// 견적 업데이트
// ===================================
function updateQuote() {
    let totalPrice = 0;
    selectedProducts.forEach(product => {
        totalPrice += product.final_price;
    });
    
    document.getElementById('totalCount').textContent = `${selectedProducts.size}개`;
    document.getElementById('finalPrice').textContent = `${numberFormat(totalPrice)}원`;
}

// ===================================
// 초기화
// ===================================
function resetAll() {
    if (!confirm('모든 선택을 초기화하시겠습니까?')) return;
    
    selectedProducts.clear();
    
    // 모든 선택 상태 해제
    document.querySelectorAll('.product-item.selected').forEach(item => {
        item.classList.remove('selected');
    });
    
    // 필터 초기화
    resetFilters();
    updateFilterGrid();
    applyFilters();
    
    updateSelectedList();
    updateQuote();
}

// ===================================
// 견적서 인쇄
// ===================================
function printQuote() {
    let productListHtml = '';
    
    // 선택된 제품들을 배열로 변환하고 정렬
    const selectedProductsArray = Array.from(selectedProducts.values());
    
    // 분류별로 그룹화
    const groupedProducts = {};
    selectedProductsArray.forEach(product => {
        const key = product.classification;
        if (!groupedProducts[key]) {
            groupedProducts[key] = [];
        }
        groupedProducts[key].push(product);
    });
    
    // 정렬된 순서로 HTML 생성
    Object.keys(groupedProducts).sort(koreanFirstSort).forEach(classification => {
        const sortedProducts = sortProducts(groupedProducts[classification]);
        
        sortedProducts.forEach(product => {
            productListHtml += `
                <tr>
                    <td>${product.product_name}</td>
                    <td>${product.classification}</td>
                    <td>${product.category_sub}</td>
                    <td style="text-align: right;">${numberFormat(product.final_price)}원</td>
                </tr>
            `;
        });
    });
    
    const totalPrice = document.getElementById('finalPrice').textContent;
    
    const printContent = `
        <html>
        <head>
            <title>견적서 - 픽셀창고</title>
            <style>
                body { font-family: 'Pretendard', sans-serif; padding: 40px; }
                h1 { text-align: center; margin-bottom: 40px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; font-weight: 600; }
                .summary { margin-top: 30px; text-align: right; }
                .summary p { margin: 8px 0; }
                .final { font-size: 20px; font-weight: bold; color: #2563eb; }
                .footer { margin-top: 50px; text-align: center; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <h1>PC부품 매입 견적서</h1>
            <p><strong>발행일:</strong> ${new Date().toLocaleDateString('ko-KR')}</p>
            
            <table>
                <thead>
                    <tr>
                        <th>제품명</th>
                        <th>분류</th>
                        <th>소분류</th>
                        <th style="text-align: right;">매입가</th>
                    </tr>
                </thead>
                <tbody>
                    ${productListHtml}
                </tbody>
            </table>
            
            <div class="summary">
                <p>선택 제품 수: ${selectedProducts.size}개</p>
                <p class="final">총 매입 견적가: ${totalPrice}</p>
            </div>
            
            <div class="footer">
                <p>픽셀창고 | 전화: 02-381-5552</p>
                <p>* 본 견적서는 예상 매입가이며, 실제 검수 후 최종 가격이 확정됩니다.</p>
            </div>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// ===================================
// 문의하기
// ===================================
async function submitInquiry() {
    if (selectedProducts.size === 0) {
        alert('제품을 선택해주세요.');
        return;
    }
    
    // 선택된 제품 정보 정리
    let productInfo = [];
    let totalPrice = 0;
    let productsByCategory = new Map();
    
    // 카테고리별로 제품 그룹화
    selectedProducts.forEach(product => {
        productInfo.push(`- [${product.classification}] ${product.product_name} (${product.category_sub}) - ${numberFormat(product.final_price)}원`);
        totalPrice += product.final_price;
        
        // 카테고리별 분류
        const category = product.category_sub || product.classification;
        if (!productsByCategory.has(category)) {
            productsByCategory.set(category, []);
        }
        productsByCategory.get(category).push(product.product_name);
    });
    
    // 브랜드/모델 정보 생성
    let brandModel = [];
    productsByCategory.forEach((products, category) => {
        brandModel.push(`${category}: ${products.join(', ')}`);
    });
    
    // 메시지 생성
    const message = `[자동견적 시스템으로 선택한 제품]\n\n` +
                   `${productInfo.join('\n')}\n\n` +
                   `예상 총 견적가: ${numberFormat(totalPrice)}원`;
    
    // 자동견적 데이터
    const autoQuoteData = {
        isAutoQuote: true,
        deviceType: 'pc_parts',
        brandModel: brandModel.join(' / '),
        message: message,
        products: Array.from(selectedProducts.values()),
        totalPrice: totalPrice,
        timestamp: new Date().toISOString()
    };
    
    // 모달 모드인지 확인 (iframe 내부인지 확인)
    if (window.isModalMode || window.parent !== window) {
        // 부모 창에 메시지 전송
        window.parent.postMessage({
            type: 'autoQuoteSubmit',
            autoQuoteData: autoQuoteData
        }, '*');
        return;
    }
    
    // 일반 모드: 로컬 스토리지에 저장 후 페이지 이동
    localStorage.setItem('autoQuoteData', JSON.stringify(autoQuoteData));
    window.location.href = '/#quote';
}

// ===================================
// 유틸리티
// ===================================
function numberFormat(num) {
    return new Intl.NumberFormat('ko-KR').format(num);
}