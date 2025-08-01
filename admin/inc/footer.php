<?php
/*
 * 파일명: footer.php
 * 위치: /admin/inc/footer.php
 * 기능: 관리자 페이지 공통 푸터
 * 작성일: 2025-01-31
 */
?>
        </div><!-- .admin-container -->
    </main><!-- .admin-main -->
    
    <!-- ===================================
     * 푸터
     * ===================================
     -->
    <footer class="admin-footer">
        <div class="admin-container">
            <p>&copy; 2025 픽셀창고. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- ===================================
     * 공통 스크립트
     * ===================================
     -->
    <script>
        // 사이드바 토글
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        }
        
        // 사이드바 상태 복원
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
        
        // 드롭다운 토글
        document.querySelector('.header-user').addEventListener('click', function() {
            this.classList.toggle('active');
        });
        
        // 외부 클릭 시 드롭다운 닫기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-user')) {
                document.querySelector('.header-user').classList.remove('active');
            }
        });
    </script>
</body>
</html>