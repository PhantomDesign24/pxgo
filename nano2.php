<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나노메모리 스마트 파서</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .main-categories {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .main-cat-btn {
            padding: 15px;
            border: 2px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .main-cat-btn:hover {
            background: #f0f0f0;
            border-color: #007bff;
        }
        
        .main-cat-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .category-detail {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            display: none;
        }
        
        .category-detail.show {
            display: block;
        }
        
        .category-tree {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .tree-item {
            padding: 5px 0;
            cursor: pointer;
        }
        
        .tree-item:hover {
            background: #e9ecef;
        }
        
        .tree-item input {
            margin-right: 8px;
        }
        
        .tree-item.depth-0 { margin-left: 0px; font-weight: bold; }
        .tree-item.depth-1 { margin-left: 20px; }
        .tree-item.depth-2 { margin-left: 40px; }
        .tree-item.depth-3 { margin-left: 60px; }
        
        .tree-item.is-final {
            color: #666;
            font-style: italic;
        }
        
        .status {
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .status.loading { background: #e3f2fd; color: #1976d2; }
        .status.success { background: #e8f5e9; color: #388e3c; }
        .status.error { background: #ffebee; color: #d32f2f; }
        
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #28a745;
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .log-section {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            font-family: monospace;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .results-section {
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .price {
            text-align: right;
            font-weight: bold;
        }
        
        .stats-box {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            background: #f0f0f0;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .duplicate-info {
            color: #ff9800;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>나노메모리 스마트 파서 (중복 제거)</h1>
        
        <div class="status" id="status">메인 카테고리를 선택하세요</div>
        
        <!-- 전체 파싱 버튼 추가 -->
        <div style="text-align: center; margin: 20px 0;">
            <button onclick="parseAllCategories()" class="btn-primary" id="parseAllBtn" style="font-size: 18px; padding: 15px 30px;">
                🚀 모든 카테고리 한번에 파싱 (중복 제거)
            </button>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <!-- 메인 카테고리 버튼들 -->
        <h3>또는 개별 카테고리 선택:</h3>
        <div class="main-categories" id="mainCategories"></div>
        
        <!-- 선택된 카테고리 상세 -->
        <div class="category-detail" id="categoryDetail">
            <h3 id="categoryTitle"></h3>
            <div class="category-tree" id="categoryTree"></div>
            
            <div style="margin-top: 20px;">
                <label>페이지 제한: 
                    <input type="number" id="maxPages" value="99" min="1" max="99" style="width: 60px;">
                </label>
                <button onclick="selectAllInCategory()" class="btn-secondary">모두 선택</button>
                <button onclick="deselectAllInCategory()" class="btn-secondary">모두 해제</button>
                <button onclick="startParsing()" class="btn-primary" id="startBtn">선택한 카테고리 파싱</button>
            </div>
        </div>
        
        <!-- 진행률 -->
        <div class="progress-bar" id="progressBar" style="display: none;">
            <div class="progress-fill" id="progressFill">0%</div>
        </div>
        
        <!-- 로그 -->
        <div class="log-section" id="logSection" style="display: none;"></div>
        
        <!-- 결과 -->
        <div class="results-section" id="results"></div>
    </div>
    
    <script>
        let mainCategories = {};
        let currentCategory = null;
        let currentCategoryData = null;
        let allProducts = [];
        let isRunning = false;
        let allCategoriesData = {}; // 모든 카테고리 데이터 저장
        let totalDuplicatesSkipped = 0; // 중복 건너뛴 수
        
        // 카테고리 깊이 계산 (>의 개수로 판단)
        function getCategoryDepth(subcategory) {
            if (!subcategory) return 0;
            return (subcategory.match(/>/g) || []).length;
        }
        
        // 전체 카테고리 파싱
        async function parseAllCategories() {
            if (!confirm('모든 카테고리를 파싱하시겠습니까? 시간이 오래 걸릴 수 있습니다.')) {
                return;
            }
            
            isRunning = true;
            allProducts = [];
            allCategoriesData = {};
            totalDuplicatesSkipped = 0;
            
            // 클라이언트 사이드 중복 체크를 위한 Map (더 상세한 카테고리 정보 유지)
            const productMap = new Map();
            
            document.getElementById('parseAllBtn').disabled = true;
            document.getElementById('logSection').style.display = 'block';
            document.getElementById('logSection').innerHTML = '';
            document.getElementById('progressBar').style.display = 'block';
            
            showStatus('모든 카테고리 로딩 중...', 'loading');
            addLog('전체 카테고리 파싱 시작 (클라이언트 중복 제거 활성화)');
            
            // 1단계: 모든 카테고리 구조 로드
            for (const [name, code] of Object.entries(mainCategories)) {
                addLog(`${name} 카테고리 구조 로딩 중...`);
                
                try {
                    const response = await fetch(`smart_parser.php?action=getSingleCategory&category=${name}&catcode=${code}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        allCategoriesData[name] = data.categories;
                        addLog(`✓ ${name}: ${data.categories.length}개 서브카테고리`, 'success');
                    } else {
                        addLog(`✗ ${name} 로드 실패`, 'error');
                    }
                } catch (error) {
                    addLog(`✗ ${name} 오류: ${error.message}`, 'error');
                }
            }
            
            // 2단계: 모든 제품 파싱
            let totalCategories = 0;
            let completedCategories = 0;
            
            // 전체 카테고리 수 계산
            for (const cats of Object.values(allCategoriesData)) {
                totalCategories += cats.length;
            }
            
            addLog(`\n총 ${totalCategories}개 카테고리 파싱 시작`);
            showStatus(`0 / ${totalCategories} 카테고리 파싱 중...`, 'loading');
            
            // 각 카테고리별로 파싱
            for (const [mainName, categories] of Object.entries(allCategoriesData)) {
                if (!isRunning) break;
                
                for (const cat of categories) {
                    if (!isRunning) break;
                    
                    try {
                        addLog(`${cat.path} 파싱 중...`);
                        
                        const response = await fetch('smart_parser.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'parseProducts',
                                category: mainName,
                                subcategory: cat.path,
                                catcode: cat.catcode,
                                maxPages: parseInt(document.getElementById('maxPages').value) || 99
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // 클라이언트 사이드에서 중복 제거 (더 상세한 카테고리 유지)
                            let newProducts = 0;
                            let skipped = 0;
                            
                            result.products.forEach(product => {
                                // 제품명과 가격으로 고유 키 생성
                                const productKey = `${product.제품명}_${product.가격}`;
                                
                                const existingProduct = productMap.get(productKey);
                                
                                if (!existingProduct) {
                                    // 새 제품
                                    productMap.set(productKey, product);
                                    newProducts++;
                                } else {
                                    // 이미 존재하는 제품 - 더 상세한 카테고리 정보를 가진 것으로 교체
                                    const existingDepth = getCategoryDepth(existingProduct.소분류);
                                    const newDepth = getCategoryDepth(product.소분류);
                                    
                                    if (newDepth > existingDepth) {
                                        // 새 제품이 더 상세한 카테고리 정보를 가짐
                                        productMap.set(productKey, product);
                                        addLog(`  → 더 상세한 카테고리로 업데이트: ${product.소분류}`, 'info');
                                    }
                                    skipped++;
                                }
                            });
                            
                            totalDuplicatesSkipped += skipped;
                            
                            if (skipped > 0) {
                                addLog(`✓ ${newProducts}개 신규 제품 (${skipped}개 중복 처리)`, 'success');
                            } else {
                                addLog(`✓ ${newProducts}개 제품 (총 ${result.totalPages}페이지)`, 'success');
                            }
                        } else {
                            addLog(`✗ 실패: ${result.error}`, 'error');
                        }
                    } catch (error) {
                        addLog(`✗ 오류: ${error.message}`, 'error');
                    }
                    
                    completedCategories++;
                    updateProgress(completedCategories, totalCategories);
                    showStatus(`${completedCategories} / ${totalCategories} 카테고리 파싱 중...`, 'loading');
                    
                    // 서버 부하 방지를 위한 딜레이
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
            }
            
            // Map을 배열로 변환
            allProducts = Array.from(productMap.values());
            
            finishAllParsing();
        }
        
        // 전체 파싱 완료
        function finishAllParsing() {
            document.getElementById('parseAllBtn').disabled = false;
            isRunning = false;
            
            if (allProducts.length > 0) {
                showStatus(`완료! 총 ${allProducts.length}개 고유 제품 (${totalDuplicatesSkipped}개 중복 제거)`, 'success');
                addLog(`\n파싱 완료! 총 ${allProducts.length}개 고유 제품 (${totalDuplicatesSkipped}개 중복 제거됨)`, 'success');
                displayAllResults();
            } else {
                showStatus('수집된 제품이 없습니다.', 'error');
                addLog('수집된 제품이 없습니다.', 'error');
            }
        }
        
        // 전체 결과 표시
        function displayAllResults() {
            const results = document.getElementById('results');
            
            // 카테고리별 통계
            const stats = {};
            allProducts.forEach(p => {
                if (!stats[p.대분류]) {
                    stats[p.대분류] = { count: 0, totalPrice: 0 };
                }
                stats[p.대분류].count++;
                stats[p.대분류].totalPrice += p.가격;
            });
            
            let statsHtml = '<h3>카테고리별 통계</h3><div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 20px;">';
            
            for (const [cat, data] of Object.entries(stats)) {
                const avgPrice = Math.round(data.totalPrice / data.count);
                statsHtml += `
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center;">
                        <h4>${cat}</h4>
                        <div>${data.count}개 제품</div>
                        <div style="font-size: 0.9em; color: #666;">평균: ${avgPrice.toLocaleString()}원</div>
                    </div>
                `;
            }
            
            statsHtml += '</div>';
            
            let html = `
                ${statsHtml}
                <h3>전체 결과</h3>
                <div>
                    <span class="stats-box">총 ${allProducts.length}개 고유 제품</span>
                    <span class="stats-box" style="background-color: #ffe0b2;">중복 제거: ${totalDuplicatesSkipped}개</span>
                </div>
                <div style="margin-top: 10px;">
                    <button onclick="downloadAllCSV()" class="btn-success">전체 CSV 다운로드</button>
                    <button onclick="downloadAllJSON()" class="btn-success">전체 JSON 다운로드</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>대분류</th>
                            <th>소분류</th>
                            <th>분류</th>
                            <th>제품명</th>
                            <th class="price">가격</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            // 처음 100개만 표시
            allProducts.slice(0, 100).forEach(p => {
                html += `
                    <tr>
                        <td>${p.대분류}</td>
                        <td>${p.소분류}</td>
                        <td>${p.분류}</td>
                        <td>${p.제품명}</td>
                        <td class="price">${p.가격.toLocaleString()}원</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            
            if (allProducts.length > 100) {
                html += `<p>... 외 ${allProducts.length - 100}개 (전체 데이터는 다운로드하세요)</p>`;
            }
            
            results.innerHTML = html;
        }
        
        // 전체 CSV 다운로드
        function downloadAllCSV() {
            let csv = '\ufeff대분류,소분류,분류,제품명,가격\n';
            
            allProducts.forEach(p => {
                csv += `"${p.대분류}","${p.소분류}","${p.분류}","${p.제품명}",${p.가격}\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `nanomemory_all_unique_${new Date().getTime()}.csv`;
            link.click();
        }
        
        // 전체 JSON 다운로드
        function downloadAllJSON() {
            const json = JSON.stringify(allProducts, null, 2);
            const blob = new Blob([json], { type: 'application/json' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `nanomemory_all_unique_${new Date().getTime()}.json`;
            link.click();
        }
        
        // 페이지 로드시 메인 카테고리 가져오기
        window.onload = async function() {
            try {
                const response = await fetch('smart_parser.php?action=getMainCategories');
                const data = await response.json();
                
                if (data.success) {
                    mainCategories = data.categories;
                    displayMainCategories();
                }
            } catch (error) {
                showStatus('메인 카테고리 로드 실패', 'error');
            }
        };
        
        // 메인 카테고리 표시
        function displayMainCategories() {
            const container = document.getElementById('mainCategories');
            
            for (const [name, code] of Object.entries(mainCategories)) {
                const btn = document.createElement('button');
                btn.className = 'main-cat-btn';
                btn.textContent = name;
                btn.onclick = () => loadCategory(name, code);
                container.appendChild(btn);
            }
        }
        
        // 카테고리 로드
        async function loadCategory(name, code) {
            // 버튼 활성화 상태 변경
            document.querySelectorAll('.main-cat-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent === name) {
                    btn.classList.add('active');
                }
            });
            
            showStatus(`${name} 카테고리 로딩 중...`, 'loading');
            currentCategory = name;
            
            try {
                const response = await fetch(`smart_parser.php?action=getSingleCategory&category=${name}&catcode=${code}`);
                const data = await response.json();
                
                if (data.success) {
                    currentCategoryData = data;
                    displayCategoryDetail(name, data.categories);
                    showStatus(`${name} 카테고리 로드 완료 (${data.categories.length}개)`, 'success');
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showStatus(`오류: ${error.message}`, 'error');
            }
        }
        
        // 카테고리 상세 표시
        function displayCategoryDetail(name, categories) {
            document.getElementById('categoryTitle').textContent = name + ' 카테고리 구조';
            document.getElementById('categoryDetail').classList.add('show');
            
            const tree = document.getElementById('categoryTree');
            tree.innerHTML = '';
            
            categories.forEach(cat => {
                const item = document.createElement('div');
                item.className = `tree-item depth-${cat.depth}`;
                if (cat.is_final) {
                    item.classList.add('is-final');
                }
                
                item.innerHTML = `
                    <label>
                        <input type="checkbox" 
                               data-catcode="${cat.catcode}"
                               data-path="${cat.path}"
                               data-name="${cat.name}">
                        ${cat.name} 
                        ${cat.is_final ? '(최종)' : ''}
                        <small style="color: #999;">[${cat.catcode}]</small>
                    </label>
                `;
                
                tree.appendChild(item);
            });
        }
        
        // 상태 표시
        function showStatus(message, type = 'info') {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status ${type}`;
        }
        
        // 전체 선택/해제
        function selectAllInCategory() {
            document.querySelectorAll('#categoryTree input[type="checkbox"]').forEach(cb => cb.checked = true);
        }
        
        function deselectAllInCategory() {
            document.querySelectorAll('#categoryTree input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
        
        // 로그 추가
        function addLog(message, type = 'info') {
            const log = document.getElementById('logSection');
            log.style.display = 'block';
            
            const entry = document.createElement('div');
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            if (type === 'error') {
                entry.style.color = '#d32f2f';
            } else if (type === 'success') {
                entry.style.color = '#388e3c';
            } else if (type === 'info') {
                entry.style.color = '#1976d2';
            } else {
                entry.style.color = '#333';
            }
            
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }
        
        // 파싱 시작 (개별 카테고리)
        async function startParsing() {
            const selected = [];
            document.querySelectorAll('#categoryTree input[type="checkbox"]:checked').forEach(cb => {
                selected.push({
                    catcode: cb.dataset.catcode,
                    path: cb.dataset.path,
                    name: cb.dataset.name
                });
            });
            
            if (selected.length === 0) {
                alert('최소 하나의 카테고리를 선택하세요.');
                return;
            }
            
            isRunning = true;
            allProducts = [];
            totalDuplicatesSkipped = 0;
            
            // 클라이언트 사이드 중복 체크를 위한 Map
            const productMap = new Map();
            
            document.getElementById('startBtn').disabled = true;
            document.getElementById('logSection').innerHTML = '';
            document.getElementById('progressBar').style.display = 'block';
            
            addLog(`${selected.length}개 카테고리 파싱 시작 (중복 제거 활성화)`);
            
            let completed = 0;
            
            for (const cat of selected) {
                if (!isRunning) break;
                
                try {
                    addLog(`${cat.path} 파싱 중...`);
                    
                    const response = await fetch('smart_parser.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'parseProducts',
                            category: currentCategory,
                            subcategory: cat.path,
                            catcode: cat.catcode,
                            maxPages: parseInt(document.getElementById('maxPages').value) || 99
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // 클라이언트 사이드에서 중복 제거
                        let newProducts = 0;
                        let skipped = 0;
                        
                        result.products.forEach(product => {
                            const productKey = `${product.제품명}_${product.가격}`;
                            const existingProduct = productMap.get(productKey);
                            
                            if (!existingProduct) {
                                productMap.set(productKey, product);
                                newProducts++;
                            } else {
                                const existingDepth = getCategoryDepth(existingProduct.소분류);
                                const newDepth = getCategoryDepth(product.소분류);
                                
                                if (newDepth > existingDepth) {
                                    productMap.set(productKey, product);
                                }
                                skipped++;
                            }
                        });
                        
                        totalDuplicatesSkipped += skipped;
                        
                        if (skipped > 0) {
                            addLog(`✓ ${newProducts}개 신규 제품 (${skipped}개 중복 처리)`, 'success');
                        } else {
                            addLog(`✓ ${newProducts}개 제품 수집`, 'success');
                        }
                    } else {
                        addLog(`✗ 실패: ${result.error}`, 'error');
                    }
                } catch (error) {
                    addLog(`✗ 오류: ${error.message}`, 'error');
                }
                
                completed++;
                updateProgress(completed, selected.length);
            }
            
            // Map을 배열로 변환
            allProducts = Array.from(productMap.values());
            
            finishParsing();
        }
        
        // 진행률 업데이트
        function updateProgress(current, total) {
            const percentage = Math.round((current / total) * 100);
            document.getElementById('progressFill').style.width = percentage + '%';
            document.getElementById('progressFill').textContent = percentage + '%';
        }
        
        // 파싱 완료
        function finishParsing() {
            document.getElementById('startBtn').disabled = false;
            
            if (allProducts.length > 0) {
                addLog(`완료! 총 ${allProducts.length}개 고유 제품 (${totalDuplicatesSkipped}개 중복 제거됨)`, 'success');
                displayResults();
            } else {
                addLog('수집된 제품이 없습니다.', 'error');
            }
        }
        
        // 결과 표시
        function displayResults() {
            const results = document.getElementById('results');
            
            let html = `
                <h3>결과</h3>
                <div>
                    <span class="stats-box">총 ${allProducts.length}개 고유 제품</span>
                    <span class="stats-box" style="background-color: #ffe0b2;">중복 제거: ${totalDuplicatesSkipped}개</span>
                </div>
                <div style="margin-top: 10px;">
                    <button onclick="downloadCSV()" class="btn-success">CSV 다운로드</button>
                    <button onclick="downloadJSON()" class="btn-success">JSON 다운로드</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>대분류</th>
                            <th>소분류</th>
                            <th>분류</th>
                            <th>제품명</th>
                            <th class="price">가격</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            allProducts.slice(0, 50).forEach(p => {
                html += `
                    <tr>
                        <td>${p.대분류}</td>
                        <td>${p.소분류}</td>
                        <td>${p.분류}</td>
                        <td>${p.제품명}</td>
                        <td class="price">${p.가격.toLocaleString()}원</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            
            if (allProducts.length > 50) {
                html += `<p>... 외 ${allProducts.length - 50}개</p>`;
            }
            
            results.innerHTML = html;
        }
        
        // CSV 다운로드
        function downloadCSV() {
            let csv = '\ufeff대분류,소분류,분류,제품명,가격\n';
            
            allProducts.forEach(p => {
                csv += `"${p.대분류}","${p.소분류}","${p.분류}","${p.제품명}",${p.가격}\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `nanomemory_${currentCategory}_unique_${new Date().getTime()}.csv`;
            link.click();
        }
        
        // JSON 다운로드
        function downloadJSON() {
            const json = JSON.stringify(allProducts, null, 2);
            const blob = new Blob([json], { type: 'application/json' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `nanomemory_${currentCategory}_unique_${new Date().getTime()}.json`;
            link.click();
        }
    </script>
</body>
</html>