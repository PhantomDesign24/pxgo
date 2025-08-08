/*
 * 파일명: script.js
 * 위치: /
 * 기능: 노트북/컴퓨터 매입 사이트 JavaScript
 * 작성일: 2025-01-30
 * 수정일: 2025-01-30
 */

// ===================================
// 초기 설정
// ===================================
/* DOM 로드 완료 시 실행 */
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initScrollEffects();
    initAnimations();
    initForm();
    initInquiryTicker();
    initLiveNotifications();
});

// ===================================
// 모바일 메뉴
// ===================================
/* 모바일 메뉴 초기화 */
function initMobileMenu() {
    const menuBtn = document.querySelector('.header-menu');
    const closeBtn = document.querySelector('.mobile-menu-close');
    const mobileMenu = document.querySelector('.mobile-menu');
    const menuLinks = document.querySelectorAll('.mobile-menu-nav a');
    const ctaButtons = document.querySelectorAll('.mobile-menu-cta .btn');
    
    if (!menuBtn || !mobileMenu) return;
    
    // 메뉴 열기
    menuBtn.addEventListener('click', () => {
        mobileMenu.classList.add('active');
        menuBtn.classList.add('active');
        document.body.classList.add('menu-open');
    });
    
    // 메뉴 닫기
    function closeMenu() {
        mobileMenu.classList.remove('active');
        menuBtn.classList.remove('active');
        document.body.classList.remove('menu-open');
    }
    
    // 닫기 버튼 클릭
    if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }
    
    // 메뉴 링크 클릭 시 닫기
    menuLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // 부드러운 스크롤을 위해 기본 동작 방지
            e.preventDefault();
            const targetId = link.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                closeMenu();
                
                // 메뉴가 닫힌 후 스크롤
                setTimeout(() => {
                    const headerHeight = 64;
                    const targetTop = targetSection.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                    
                    window.scrollTo({
                        top: targetTop,
                        behavior: 'smooth'
                    });
                }, 400);
            }
        });
    });
    
    // CTA 버튼 클릭 시 닫기
    ctaButtons.forEach(button => {
        if (button.getAttribute('href').startsWith('#')) {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = button.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                
                if (targetSection) {
                    closeMenu();
                    
                    setTimeout(() => {
                        const headerHeight = 64;
                        const targetTop = targetSection.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                        
                        window.scrollTo({
                            top: targetTop,
                            behavior: 'smooth'
                        });
                    }, 400);
                }
            });
        }
    });
    
    // ESC 키로 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMenu();
        }
    });
    
    // 배경 클릭으로 닫기 (선택사항)
    mobileMenu.addEventListener('click', (e) => {
        if (e.target === mobileMenu) {
            closeMenu();
        }
    });
}

// ===================================
// 스크롤 효과
// ===================================
/* 스크롤 관련 효과 처리 */
function initScrollEffects() {
    const header = document.querySelector('.header');
    const scrollTopBtn = document.querySelector('.scroll-top');
    const navLinks = document.querySelectorAll('.header-nav a');
    const sections = document.querySelectorAll('section[id]');
    
    // header가 없으면 함수 종료
    if (!header) return;
    
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // 헤더 효과 - header가 존재하는 경우에만 실행
        if (header) {
            if (currentScroll > 50) {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 1px 0 rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 1)';
                header.style.boxShadow = '';
            }
        }
        
        // Top 버튼
        if (scrollTopBtn) {
            if (currentScroll > 500) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        }
        
        // 스크롤 스파이 - 현재 섹션 하이라이트
        updateActiveNavLink(currentScroll);
        
        lastScroll = currentScroll;
    });
    
    // Top 버튼 클릭
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/* 현재 보고 있는 섹션에 따라 네비게이션 링크 활성화 */
function updateActiveNavLink(scrollPosition) {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.header-nav a');
    const headerHeight = 80; // 헤더 높이 + 여유 공간
    
    let currentSection = '';
    
    // 현재 뷰포트에 있는 섹션 찾기
    sections.forEach(section => {
        const sectionTop = section.offsetTop - headerHeight;
        const sectionHeight = section.offsetHeight;
        
        if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
            currentSection = section.getAttribute('id');
        }
    });
    
    // 네비게이션 링크 업데이트
    navLinks.forEach(link => {
        link.classList.remove('active');
        
        // href에서 # 제거하고 비교
        const href = link.getAttribute('href').substring(1);
        if (href === currentSection) {
            link.classList.add('active');
        }
    });
    
    // 모바일 메뉴의 링크도 업데이트
    const mobileNavLinks = document.querySelectorAll('.mobile-menu-nav a');
    mobileNavLinks.forEach(link => {
        link.classList.remove('active');
        
        const href = link.getAttribute('href').substring(1);
        if (href === currentSection) {
            link.classList.add('active');
        }
    });
}

// ===================================
// 애니메이션
// ===================================
/* Intersection Observer를 이용한 스크롤 애니메이션 */
function initAnimations() {
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
    
    // 애니메이션 요소 관찰
    document.querySelectorAll('.fade-in, .slide-in').forEach(el => {
        observer.observe(el);
    });
}

// ===================================
// 폼 처리
// ===================================
/* 견적 폼 처리 - 기존 initForm 함수를 아래로 완전 교체 */
function initForm() {
    const form = document.getElementById('quoteForm');
    if (!form) return;
    
    // 전화번호 포맷팅
    const phoneInput = form.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^0-9]/g, '');
            let formatted = '';
            
            if (value.length <= 3) {
                formatted = value;
            } else if (value.length <= 7) {
                formatted = value.slice(0, 3) + '-' + value.slice(3);
            } else if (value.length <= 11) {
                formatted = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
            } else {
                // 11자리 초과시 처리
                value = value.slice(0, 11);
                formatted = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
            }
            
            e.target.value = formatted;
        });
        
        // 붙여넣기 이벤트 처리
        phoneInput.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/[^0-9]/g, '').slice(0, 11);
            
            let formatted = '';
            if (cleanedText.length <= 3) {
                formatted = cleanedText;
            } else if (cleanedText.length <= 7) {
                formatted = cleanedText.slice(0, 3) + '-' + cleanedText.slice(3);
            } else {
                formatted = cleanedText.slice(0, 3) + '-' + cleanedText.slice(3, 7) + '-' + cleanedText.slice(7);
            }
            
            e.target.value = formatted;
        });
    }
    
    // 폼 제출
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        
        // 로딩 상태
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>처리중...</span>';
        
        try {
            const formData = new FormData(form);
            const response = await fetch('process_inquiry.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // 성공 메시지
                showNotification('견적 요청이 완료되었습니다. 곧 연락드리겠습니다.', 'success');
                
                // 즉시 데이터 새로고침
                await fetchInquiryData();
                
                form.reset();
                // 이미지 미리보기 초기화
                const imagePreview = document.getElementById('imagePreview');
                const fileLabel = document.querySelector('.file-label span');
                if (imagePreview) imagePreview.innerHTML = '';
                if (fileLabel) fileLabel.textContent = '사진 선택 (최대 5장)';
            } else {
                showNotification(result.message || '오류가 발생했습니다.', 'error');
            }
        } catch (error) {
            showNotification('서버 연결에 실패했습니다.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    });
}
/* 상태 텍스트 변환 */
function getConditionText(status) {
    const conditions = {
        'excellent': '매우 좋음',
        'good': '좋음',
        'fair': '보통',
        'poor': '나쁨/고장'
    };
    return conditions[status] || '확인중';
}

// ===================================
// 알림 메시지
// ===================================
/* 알림 메시지 표시 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <p>${message}</p>
            <button class="notification-close">×</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // 닫기 버튼
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
    
    // 자동 제거
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// ===================================
// 부드러운 스크롤
// ===================================
/* 앵커 링크 스크롤 */
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        if (href === '#') return;
        
        e.preventDefault();
        const target = document.querySelector(href);
        if (!target) return;
        
        const headerHeight = 48;
        const targetTop = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;
        
        window.scrollTo({
            top: targetTop,
            behavior: 'smooth'
        });
    });
});

// ===================================
// 실시간 견적 티커
// ===================================
/* 견적 그리드 초기화 */
function initInquiryTicker() {
    const inquiriesGrid = document.getElementById('inquiriesGrid');
    if (!inquiriesGrid) return;
    
    // 서버에서 데이터 가져오기
    fetchInquiryData();
    
    // 5분마다 데이터 새로고침
    setInterval(fetchInquiryData, 300000);
}

/* 서버에서 견적 데이터 가져오기 */
async function fetchInquiryData() {
    try {
        const response = await fetch('get_stats.php');
        const data = await response.json();
        
        if (data.success) {
            // 통계 업데이트
            updateStatsDisplay(data.stats);
            
            // 그리드 업데이트
            updateInquiryGrid(data.inquiries);
        }
    } catch (error) {
        console.error('데이터 로드 실패:', error);
        
        // 에러 시 에러 메시지 표시
        const inquiriesGrid = document.getElementById('inquiriesGrid');
        if (inquiriesGrid) {
            inquiriesGrid.innerHTML = `
                <div style="
                    grid-column: 1 / -1;
                    text-align: center;
                    padding: 60px 20px;
                    color: #ef4444;
                ">
                    <i class="bi bi-exclamation-triangle" style="font-size: 48px; display: block; margin-bottom: 16px;"></i>
                    <p style="font-size: 18px; font-weight: 500;">데이터를 불러올 수 없습니다</p>
                    <p style="font-size: 14px; margin-top: 8px; color: #6b7280;">잠시 후 다시 시도해주세요</p>
                </div>
            `;
        }
    }
}

/* 통계 표시 업데이트 */
function updateStatsDisplay(stats) {
    const todayCount = document.getElementById('todayCount');
    const monthCount = document.getElementById('monthCount');
    const responseTime = document.getElementById('responseTime');
    
    if (todayCount) {
        // 애니메이션 효과로 숫자 증가
        animateValue(todayCount, 0, stats.todayCount || 0, 1000);
    }
    if (monthCount) {
        animateValue(monthCount, 0, stats.monthCount || 0, 1000);
    }
    if (responseTime) {
        responseTime.textContent = stats.responseTime || '15';
    }
}

/* 숫자 애니메이션 효과 */
function animateValue(element, start, end, duration) {
    const startTimestamp = Date.now();
    const step = () => {
        const timestamp = Date.now();
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        element.textContent = value;
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}



/* 견적 카드 생성 */
function createInquiryCard(item) {
    const card = document.createElement('div');
    card.className = 'inquiry-card fade-in';
    
    const deviceIcon = getDeviceIcon(item.deviceType);
    const deviceName = getDeviceTypeName(item.deviceType);
    const serviceIcon = item.serviceType === 'delivery' ? '📦' : '🚗';
    const serviceText = item.serviceType === 'delivery' ? '무료택배매입' : '당일출장매입';
    
    // 상태 표시
    let statusDisplay = '';
    if (item.statusClass === 'completed') {
        statusDisplay = `<div class="inquiry-card-price" style="font-size: 18px; color: #22c55e;">✓ 완료</div>`;
    } else if (item.statusClass === 'reviewing') {
        statusDisplay = `<div class="inquiry-card-price" style="font-size: 18px; color: #2563eb;">검수중</div>`;
    } else {
        statusDisplay = `<div class="inquiry-card-price" style="font-size: 18px; color: #f59e0b;">진행중</div>`;
    }
    
    // 기업 배지 (기업 고객인 경우)
    const companyBadge = item.isCompany 
        ? `<span style="
            position: absolute;
            top: 8px;
            right: 8px;
            background: #1e40af;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
          ">기업</span>` 
        : '';
    
    card.innerHTML = `
        <div class="inquiry-card-header">
            <div style="display: flex; align-items: center;">
                <div class="inquiry-card-icon ${item.deviceType}">
                    ${deviceIcon}
                </div>
                <div class="inquiry-card-info">
                    <div class="inquiry-card-name">${maskName(item.name)}님</div>
                    <div class="inquiry-card-time">${item.date}</div>
                </div>
            </div>
            <div class="inquiry-card-badge ${item.statusClass}">
                ${item.status}
            </div>
            ${companyBadge}
        </div>
        
        <div class="inquiry-card-content">
            <div class="inquiry-card-model" style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">
                ${deviceName}
                ${item.quantity > 1 ? ` (${item.quantity}개)` : ''}
            </div>
            <div class="inquiry-card-specs">
                <div class="inquiry-card-spec">
                    <i class="bi bi-calendar3"></i>
                    <span>${item.year}년</span>
                </div>
                <div class="inquiry-card-spec">
                    <i class="bi bi-star-fill"></i>
                    <span>${item.condition}</span>
                </div>
                ${item.location && item.serviceType === 'visit' ? 
                    `<div class="inquiry-card-spec">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>${item.location}</span>
                    </div>` : ''
                }
            </div>
        </div>
        
        <div class="inquiry-card-footer">
            ${statusDisplay}
            <div class="inquiry-card-service">
                <span>${serviceIcon}</span>
                <span>${serviceText}</span>
            </div>
        </div>
    `;
    
    return card;
}

/* 견적 데이터 포맷팅 */
function formatInquiryData(inquiry) {
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
    
    const createdDate = new Date(inquiry.created_at);
    const dateText = createdDate.toLocaleDateString('ko-KR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }); // 2025. 07. 30. 형식
    
    const status = statusMap[inquiry.status] || statusMap['new'];
    
    return {
        name: inquiry.name,
        deviceType: inquiry.device_type,
        model: inquiry.model || '미입력',
        year: inquiry.purchase_year || '미입력',
        condition: conditionMap[inquiry.condition_status] || '확인중',
        date: dateText,
        status: status.text,
        statusClass: status.class,
        serviceType: inquiry.service_type || 'delivery',
        location: inquiry.location,
        quantity: inquiry.quantity || 1,
        isCompany: inquiry.is_company || false,
        isTestData: inquiry.is_test_data
    };
}

/* 기기 아이콘 가져오기 */
function getDeviceIcon(deviceType) {
    const icons = {
        'pc_parts': '🔧',
        'pc_desktop': '🖥️',
        'pc_set': '🖥️',
        'monitor': '📺',
        'notebook': '💻',
        'macbook': '💻',
        'tablet': '📱',
        'nintendo': '🎮',
        'applewatch': '⌚',
        // 레거시 지원
        'laptop': '💻',
        'desktop': '🖥️'
    };
    return icons[deviceType] || '📦';
}

/* 기기 종류 이름 가져오기 */
function getDeviceTypeName(deviceType) {
    const names = {
        'pc_parts': 'PC부품',
        'pc_desktop': 'PC데스크탑',
        'pc_set': 'PC+모니터',
        'monitor': '모니터',
        'notebook': '노트북',
        'macbook': '맥북',
        'tablet': '태블릿',
        'nintendo': '닌텐도스위치',
        'applewatch': '애플워치',
        // 레거시 지원
        'laptop': '노트북',
        'desktop': '데스크탑'
    };
    return names[deviceType] || '기타';
}

/* 이름 마스킹 */
function maskName(name) {
    if (!name) return '***';
    if (name.length <= 2) return name[0] + '*';
    return name[0] + '*' + name[name.length - 1];
}

// ===================================
// 실시간 알림
// ===================================
/* 실시간 알림 초기화 */
let recentInquiries = [];
let currentNotificationIndex = 0;

function initLiveNotifications() {
    const notification = document.getElementById('liveNotification');
    const content = document.getElementById('notificationContent');
    if (!notification || !content) return;
    
    // 최근 견적 데이터 가져오기
    fetchRecentInquiries();
    
    // 5분마다 최신 데이터 갱신
    setInterval(fetchRecentInquiries, 300000);
    
    // 알림 표시 시작 (데이터를 받은 후)
    setTimeout(() => {
        showNextNotification();
        
        // 15-30초마다 다음 알림 표시
        setInterval(() => {
            showNextNotification();
        }, 15000 + Math.random() * 15000);
    }, 2000);
}

/* 최근 견적 데이터 가져오기 */
async function fetchRecentInquiries() {
    try {
        const response = await fetch('get_stats.php');
        const data = await response.json();
        
        if (data.success && data.inquiries) {
            // 오늘 날짜 구하기
            const today = new Date().toLocaleDateString('ko-KR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
            
            // 오늘 날짜의 모든 견적 필터링
            recentInquiries = data.inquiries.filter(inquiry => {
                const inquiryDate = new Date(inquiry.created_at).toLocaleDateString('ko-KR', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });
                return inquiryDate === today;
            }).slice(0, 20); // 최근 20개만 유지
        }
    } catch (error) {
        console.error('최근 견적 로드 실패:', error);
        recentInquiries = [];
    }
}

/* 다음 알림 표시 */
function showNextNotification() {
    const notification = document.getElementById('liveNotification');
    const content = document.getElementById('notificationContent');
    
    if (!notification || !content || recentInquiries.length === 0) return;
    
    // 현재 인덱스의 견적 표시
    const inquiry = recentInquiries[currentNotificationIndex];
    const deviceType = getDeviceTypeName(inquiry.device_type);
    const maskedName = maskName(inquiry.name);
    const location = inquiry.location || '온라인';
    const quantity = inquiry.quantity || 1;
    
    // 상태별 메시지
    let message = '';
    switch(inquiry.status) {
        case 'completed':
            // 완료 메시지 다양화
            const completedMessages = [
                `${maskedName}님의 <strong>${deviceType}</strong> 매입 완료!`,
                `${location}에서 <strong>${deviceType}</strong> 매입 완료`,
                `<strong>${deviceType}</strong> ${quantity}개 매입 완료`,
                `${maskedName}님 <strong>${deviceType}</strong> 거래 감사합니다`
            ];
            message = completedMessages[Math.floor(Math.random() * completedMessages.length)];
            notification.style.borderLeft = '4px solid #22c55e';
            break;
            
        case 'processing':
            // 검수 중 메시지
            const processingMessages = [
                `${maskedName}님의 <strong>${deviceType}</strong> 검수 진행중`,
                `<strong>${deviceType}</strong> ${quantity}개 검수 대기중`,
                `${location} <strong>${deviceType}</strong> 검수중`
            ];
            message = processingMessages[Math.floor(Math.random() * processingMessages.length)];
            notification.style.borderLeft = '4px solid #2563eb';
            break;
            
        case 'new':
            // 새 견적 메시지
            const newMessages = [
                `${maskedName}님이 <strong>${deviceType}</strong> 견적 요청`,
                `새로운 <strong>${deviceType}</strong> ${quantity}개 견적 접수`,
                `${location}에서 <strong>${deviceType}</strong> 문의`
            ];
            message = newMessages[Math.floor(Math.random() * newMessages.length)];
            notification.style.borderLeft = '4px solid #f59e0b';
            break;
            
        default:
            message = `${maskedName}님의 <strong>${deviceType}</strong> 견적 진행중`;
            notification.style.borderLeft = '4px solid #6b7280';
    }
    
    content.innerHTML = message;
    
    // 알림 표시
    notification.classList.add('show');
    
    // 5초 후 숨기기
    setTimeout(() => {
        notification.classList.remove('show');
    }, 5000);
    
    // 다음 인덱스로 이동 (순환)
    currentNotificationIndex = (currentNotificationIndex + 1) % recentInquiries.length;
}

// ===================================
// 통계 업데이트 (서버에서)
// ===================================
/* 서버에서 최신 통계 가져오기 */
async function updateStatsFromServer() {
    try {
        const response = await fetch('get_stats.php');
        const data = await response.json();
        
        if (data.success) {
            updateStatsDisplay(data.stats);
        }
    } catch (error) {
        console.error('통계 업데이트 실패:', error);
    }
}

// ===================================
// 자동견적 데이터 확인 및 적용
// ===================================
// checkAutoQuoteData 함수 내에 추가할 코드
function checkAutoQuoteData() {
    // 세션 스토리지에서 자동견적 데이터 확인
    const autoQuoteMessage = sessionStorage.getItem('autoQuoteMessage');
    const autoQuoteProducts = sessionStorage.getItem('autoQuoteProducts');
    const autoQuoteFinalPrice = sessionStorage.getItem('autoQuoteFinalPrice');
    
    if (autoQuoteMessage && autoQuoteProducts) {
        // 메시지 영역에 자동견적 정보 표시
        const messageTextarea = document.querySelector('textarea[name="message"]');
        if (messageTextarea) {
            messageTextarea.value = autoQuoteMessage;
        }
        
        // 기기 종류를 'pc_parts'로 설정 (PC부품 자동견적이므로)
        const deviceTypeSelect = document.querySelector('select[name="device_type"]');
        if (deviceTypeSelect) {
            deviceTypeSelect.value = 'pc_parts';
        }
        
        // 자동견적 여부를 표시하는 hidden 필드 추가
        const form = document.getElementById('quoteForm');
        if (form) {
            const autoQuoteField = document.createElement('input');
            autoQuoteField.type = 'hidden';
            autoQuoteField.name = 'is_auto_quote';
            autoQuoteField.value = '1';
            form.appendChild(autoQuoteField);
            
            // 예상 견적가도 추가
            const priceField = document.createElement('input');
            priceField.type = 'hidden';
            priceField.name = 'auto_quote_price';
            priceField.value = autoQuoteFinalPrice;
            form.appendChild(priceField);
        }
        
        // 자동견적 알림 표시
        showAutoQuoteNotification();
        
        // 세션 스토리지 클리어 (한 번만 사용)
        sessionStorage.removeItem('autoQuoteMessage');
        sessionStorage.removeItem('autoQuoteProducts');
        sessionStorage.removeItem('autoQuoteFinalPrice');
        
        // 스크롤하여 폼으로 이동
        setTimeout(() => {
            const quoteSection = document.getElementById('quote');
            if (quoteSection) {
                quoteSection.scrollIntoView({ behavior: 'smooth' });
            }
        }, 500);
    }
}

// ===================================
// 자동견적 알림 표시
// ===================================
function showAutoQuoteNotification() {
    const notification = document.createElement('div');
    notification.className = 'auto-quote-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="bi bi-check-circle"></i>
            <div>
                <strong>자동견적 정보가 입력되었습니다</strong>
                <p>나머지 정보를 입력하고 견적을 요청해주세요.</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // 애니메이션 적용
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // 5초 후 자동 제거
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}