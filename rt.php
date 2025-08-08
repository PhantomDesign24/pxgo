    <!-- 실시간 견적 현황 섹션 -->
    <section class="realtime-quotes-section">
        <div class="quotes-container">
            <!-- 헤더 -->
            <div class="quotes-section-header fade-in-element">
                <div class="live-status-badge">
                    <span class="live-status-indicator"></span>
                    <span class="live-status-text">실시간 업데이트</span>
                </div>
                
                <h2 class="quotes-section-title">실시간 견적 현황</h2>
                <p class="quotes-section-subtitle">지금 이 순간에도 계속되는 투명한 거래</p>
                
                <!-- 통계 카드 -->
                <div class="quotes-statistics">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-number" id="todayQuoteCount">0</div>
                        <div class="stat-title">오늘 견적</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number" id="monthlyDealCount">0</div>
                        <div class="stat-title">이번달 거래</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-number" id="avgResponseTime">15</div>
                        <div class="stat-title">평균 응답(분)</div>
                    </div>
                </div>
            </div>
            
            <!-- 무한 슬라이드 컨테이너 -->
            <div class="quotes-slider-container">
                <div class="quotes-slider-track" id="quotesSliderTrack">
                    <!-- 동적으로 생성될 카드들 -->
                </div>
            </div>
        </div>
    </section>
    <script>
        // ===================================
        // 실시간 견적 현황 무한 슬라이드
        // ===================================
        
        // 전역 변수
        let allQuotesData = [];
        
        // 초기화
        document.addEventListener('DOMContentLoaded', function() {
            loadQuotesData();
            observeFadeInElements();
            
            // 5분마다 데이터 새로고침
            setInterval(loadQuotesData, 300000);
        });
        
        // 서버에서 데이터 가져오기
        async function loadQuotesData() {
            try {
                const response = await fetch('get_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // 통계 업데이트
                    updateQuoteStatistics(data.stats);
                    
                    // 견적 데이터 저장
                    allQuotesData = data.inquiries || [];
                    
                    // 슬라이더 초기화
                    if (allQuotesData.length > 0) {
                        initInfiniteSlider();
                    } else {
                        showEmptyState();
                    }
                }
            } catch (error) {
                console.error('데이터 로드 오류:', error);
                showErrorState();
            }
        }
        
        // 통계 업데이트
        function updateQuoteStatistics(stats) {
            const todayCount = document.getElementById('todayQuoteCount');
            const monthCount = document.getElementById('monthlyDealCount');
            const responseTime = document.getElementById('avgResponseTime');
            
            if (todayCount) {
                animateNumber(todayCount, parseInt(todayCount.textContent), stats.todayCount || 0, 1000);
            }
            if (monthCount) {
                animateNumber(monthCount, parseInt(monthCount.textContent), stats.monthCount || 0, 1000);
            }
            if (responseTime) {
                responseTime.textContent = stats.responseTime || '15';
            }
        }
        
        // 숫자 애니메이션
        function animateNumber(element, start, end, duration) {
            const startTime = Date.now();
            const animate = () => {
                const currentTime = Date.now();
                const progress = Math.min((currentTime - startTime) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                element.textContent = value;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            requestAnimationFrame(animate);
        }
        
        // 무한 슬라이더 초기화
        function initInfiniteSlider() {
            const track = document.getElementById('quotesSliderTrack');
            if (!track) return;
            
            // 기존 내용 초기화
            track.innerHTML = '';
            
            // 최소 8개 이상의 데이터가 필요 (화면을 채우기 위해)
            let dataToShow = [...allQuotesData];
            while (dataToShow.length < 8) {
                dataToShow = [...dataToShow, ...allQuotesData];
            }
            
            // 카드 생성 (2세트 - 무한 루프를 위해)
            const cards = [];
            for (let set = 0; set < 2; set++) {
                dataToShow.forEach(quote => {
                    const formattedData = formatQuoteData(quote);
                    const card = createQuoteCard(formattedData);
                    cards.push(card);
                });
            }
            
            // DOM에 추가
            cards.forEach(card => track.appendChild(card));
        }
        
        // 빈 상태 표시
        function showEmptyState() {
            const track = document.getElementById('quotesSliderTrack');
            if (!track) return;
            
            track.innerHTML = `
                <div style="width: 100%; text-align: center; padding: 60px 20px; color: #6b7280;">
                    <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px;"></i>
                    <p style="font-size: 18px; font-weight: 500;">아직 견적 내역이 없습니다</p>
                    <p style="font-size: 14px; margin-top: 8px;">새로운 견적이 들어오면 여기에 표시됩니다</p>
                </div>
            `;
        }
        
        // 에러 상태 표시
        function showErrorState() {
            const track = document.getElementById('quotesSliderTrack');
            if (!track) return;
            
            track.innerHTML = `
                <div style="width: 100%; text-align: center; padding: 60px 20px; color: #ef4444;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 48px; display: block; margin-bottom: 16px;"></i>
                    <p style="font-size: 18px; font-weight: 500;">데이터를 불러올 수 없습니다</p>
                    <p style="font-size: 14px; margin-top: 8px; color: #6b7280;">잠시 후 다시 시도해주세요</p>
                </div>
            `;
        }
        
        // 견적 카드 생성
        function createQuoteCard(data) {
            const card = document.createElement('div');
            card.className = 'quote-item-card';
            card.dataset.quoteId = data.id;
            
            // 상태별 표시
            let statusDisplay = '';
            if (data.statusClass === 'completed') {
                statusDisplay = '<div class="quote-status-display" style="color: #22c55e;">✓ 완료</div>';
            } else if (data.statusClass === 'reviewing') {
                statusDisplay = '<div class="quote-status-display" style="color: #2563eb;">검수중</div>';
            } else {
                statusDisplay = '<div class="quote-status-display" style="color: #f59e0b;">진행중</div>';
            }
            
            // 기업 배지
            const companyBadge = data.isCompany ? '<span class="company-badge">기업</span>' : '';
            
            card.innerHTML = `
                ${companyBadge}
                <div class="quote-card-header">
                    <div class="quote-user-info">
                        <div class="quote-device-icon">
                            ${data.deviceIcon}
                        </div>
                        <div class="quote-user-details">
                            <div class="quote-user-name">${data.maskedName}님</div>
                            <div class="quote-time">${data.dateText}</div>
                        </div>
                    </div>
                    <div class="quote-status-badge status-${data.statusClass}">
                        ${data.statusText}
                    </div>
                </div>
                
                <div class="quote-card-content">
                    <div class="quote-device-name">
                        ${data.deviceName}
                        ${data.quantity > 1 ? ` (${data.quantity}개)` : ''}
                    </div>
                    <div class="quote-specs">
                        <div class="quote-spec-item">
                            <i class="bi bi-calendar3"></i>
                            <span>${data.year}년</span>
                        </div>
                        <div class="quote-spec-item">
                            <i class="bi bi-star-fill"></i>
                            <span>${data.condition}</span>
                        </div>
                        ${data.location && data.serviceType === 'visit' ? 
                            `<div class="quote-spec-item">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span>${data.location}</span>
                            </div>` : ''
                        }
                    </div>
                </div>
                
                <div class="quote-card-footer">
                    ${statusDisplay}
                    <div class="quote-service-type">
                        <span>${data.serviceIcon}</span>
                        <span>${data.serviceText}</span>
                    </div>
                </div>
            `;
            
            return card;
        }
        
        // 견적 데이터 포맷팅
        function formatQuoteData(quote) {
            const deviceIcons = {
                'pc_parts': '🔧',
                'pc_desktop': '🖥️',
                'pc_set': '🖥️',
                'monitor': '📺',
                'notebook': '💻',
                'macbook': '💻',
                'tablet': '📱',
                'nintendo': '🎮',
                'applewatch': '⌚'
            };
            
            const deviceNames = {
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
            
            const conditionMap = {
                'excellent': '매우 좋음',
                'good': '좋음',
                'fair': '보통',
                'poor': '나쁨/고장'
            };
            
            const statusMap = {
                'new': { text: '견적 진행중', class: 'processing' },
                'processing': { text: '검수 대기', class: 'reviewing' },
                'completed': { text: '견적 완료', class: 'completed' },
                'cancelled': { text: '취소됨', class: 'cancelled' }
            };
            
            const createdDate = new Date(quote.created_at);
            const dateText = createdDate.toLocaleDateString('ko-KR', {
                month: '2-digit',
                day: '2-digit'
            });
            
            const status = statusMap[quote.status] || statusMap['new'];
            
            return {
                id: quote.id,
                maskedName: maskUserName(quote.name),
                deviceType: quote.device_type,
                deviceIcon: deviceIcons[quote.device_type] || '📦',
                deviceName: deviceNames[quote.device_type] || '기타',
                year: quote.purchase_year || '미입력',
                condition: conditionMap[quote.condition_status] || '확인중',
                dateText: dateText,
                statusText: status.text,
                statusClass: status.class,
                serviceType: quote.service_type || 'delivery',
                serviceIcon: quote.service_type === 'delivery' ? '📦' : '🚗',
                serviceText: quote.service_type === 'delivery' ? '무료택배매입' : '당일출장매입',
                location: quote.location,
                quantity: quote.quantity || 1,
                isCompany: quote.is_company || false
            };
        }
        
        // 이름 마스킹
        function maskUserName(name) {
            if (!name) return '***';
            if (name.length <= 2) return name[0] + '*';
            return name[0] + '*' + name[name.length - 1];
        }
        
        // Fade in 애니메이션 관찰
        function observeFadeInElements() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.fade-in-element').forEach(el => {
                observer.observe(el);
            });
        }
    </script>