// assets/js/sidebar.js - ОБНОВЛЕННАЯ ВЕРСИЯ ДЛЯ САЙДБАРА 1С
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔹 Sidebar 1C initialization started');
    
    // Элементы
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const accordionHeaders = document.querySelectorAll('.accordion-header-1c');

    // Проверяем существование элементов
    if (!sidebar) {
        console.error('❌ Sidebar element not found');
        return;
    }

    console.log('✅ Sidebar element found');

    // 🔹 ПЕРЕКЛЮЧЕНИЕ САЙДБАРА
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            console.log('🔄 Toggling sidebar');
            sidebar.classList.toggle('collapsed');
            
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
            
            // Меняем направление стрелки
            if (sidebar.classList.contains('collapsed')) {
                toggleBtn.innerHTML = '›';
            } else {
                toggleBtn.innerHTML = '‹';
            }
        });
    }

    // 🔹 АККОРДЕОН - РАБОТАЮЩАЯ ВЕРСИЯ
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            console.log('📂 Toggling accordion');
            const accordionId = this.dataset.accordion;
            const content = document.getElementById(`${accordionId}-menu`);
            
            if (content) {
                // Переключаем текущий аккордеон
                this.classList.toggle('active');
                content.classList.toggle('show');
                
                // Сохраняем состояние в localStorage
                const isOpen = this.classList.contains('active');
                localStorage.setItem(`accordion_${accordionId}`, isOpen);
            }
        });
        
        // Восстанавливаем состояние аккордеонов из localStorage
        const accordionId = header.dataset.accordion;
        const content = document.getElementById(`${accordionId}-menu`);
        if (content) {
            const savedState = localStorage.getItem(`accordion_${accordionId}`);
            if (savedState === 'true') {
                header.classList.add('active');
                content.classList.add('show');
            }
        }
    });

    // 🔹 ПОДСВЕТКА АКТИВНОГО РАЗДЕЛА - ИСПРАВЛЕННАЯ
    function highlightActiveSection() {
        const currentPath = window.location.pathname;
        const currentPage = currentPath.split('/').pop() || 'index.php';
        console.log('📍 Current page:', currentPage);
        
        // Сбрасываем все активные состояния
        document.querySelectorAll('.sidebar-item-1c, .sidebar-subitem-1c, .accordion-header-1c').forEach(el => {
            el.classList.remove('active');
        });
        
        // Ищем активные ссылки
        let activeFound = false;
        
        document.querySelectorAll('.sidebar-item-1c, .sidebar-subitem-1c').forEach(link => {
            const href = link.getAttribute('href');
            if (href) {
                const linkPage = href.split('/').pop();
                
                // Проверяем совпадение страниц
                if (linkPage === currentPage || 
                    (currentPage === 'index.php' && linkPage === '') ||
                    (currentPage === '' && linkPage === 'index.php')) {
                    
                    link.classList.add('active');
                    activeFound = true;
                    
                    // Если это подпункт, открываем родительский аккордеон
                    if (link.classList.contains('sidebar-subitem-1c')) {
                        const accordion = link.closest('.accordion-1c');
                        if (accordion) {
                            const accordionHeader = accordion.querySelector('.accordion-header-1c');
                            const accordionContent = accordion.querySelector('.accordion-content-1c');
                            if (accordionHeader && accordionContent) {
                                accordionHeader.classList.add('active');
                                accordionContent.classList.add('show');
                                
                                // Сохраняем состояние
                                const accordionId = accordionHeader.dataset.accordion;
                                localStorage.setItem(`accordion_${accordionId}`, 'true');
                            }
                        }
                    }
                }
            }
        });
        
        // Если не нашли совпадений, активируем главную
        if (!activeFound && (currentPage === 'index.php' || currentPage === '')) {
            const homeLink = document.querySelector('a[href="index.php"]');
            if (homeLink) {
                homeLink.classList.add('active');
            }
        }
        
        console.log('✅ Active section highlighted');
    }

    // 🔹 МОБИЛЬНОЕ МЕНЮ - ОБНОВЛЕННОЕ
    function setupMobileMenu() {
        if (!mobileToggle) {
            console.log('ℹ️ Mobile toggle not found, creating one');
            
            const mobileToggle = document.createElement('button');
            mobileToggle.id = 'mobileMenuToggle';
            mobileToggle.className = 'mobile-menu-toggle';
            mobileToggle.innerHTML = '☰ Меню';
            mobileToggle.style.cssText = `
                display: none;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: #8b6914;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 0;
                cursor: pointer;
                font-size: 14px;
                border: 1px solid #d4c49e;
            `;
            
            document.body.appendChild(mobileToggle);
            
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }

        // Показываем/скрываем мобильное меню
        function checkMobile() {
            const mobileBtn = document.getElementById('mobileMenuToggle');
            if (window.innerWidth <= 768) {
                if (mobileBtn) mobileBtn.style.display = 'block';
                sidebar.classList.remove('collapsed');
            } else {
                if (mobileBtn) mobileBtn.style.display = 'none';
                sidebar.classList.remove('mobile-open');
            }
        }
        
        window.addEventListener('resize', checkMobile);
        checkMobile();
    }

    // 🔹 АВТО-ОТКРЫТИЕ АККОРДЕОНОВ ПРИ ЗАГРУЗКЕ
    function autoOpenAccordions() {
        // Открываем аккордеоны, у которых есть активные подпункты
        document.querySelectorAll('.sidebar-subitem-1c.active').forEach(activeItem => {
            const accordion = activeItem.closest('.accordion-1c');
            if (accordion) {
                const accordionHeader = accordion.querySelector('.accordion-header-1c');
                const accordionContent = accordion.querySelector('.accordion-content-1c');
                if (accordionHeader && accordionContent) {
                    accordionHeader.classList.add('active');
                    accordionContent.classList.add('show');
                }
            }
        });
    }

    // 🔹 ИНИЦИАЛИЗАЦИЯ
    highlightActiveSection();
    autoOpenAccordions();
    setupMobileMenu();
    
    console.log('🎉 Sidebar 1C initialized successfully');
});

// 🔹 ДОПОЛНИТЕЛЬНЫЕ ФУНКЦИИ ДЛЯ ВНЕШНЕГО ВЫЗОВА
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    if (sidebar && toggleBtn) {
        sidebar.classList.toggle('collapsed');
        if (mainContent) mainContent.classList.toggle('expanded');
        
        if (sidebar.classList.contains('collapsed')) {
            toggleBtn.innerHTML = '›';
        } else {
            toggleBtn.innerHTML = '‹';
        }
    }
}

function toggleAccordion(accordionId) {
    const header = document.querySelector(`[data-accordion="${accordionId}"]`);
    const content = document.getElementById(`${accordionId}-menu`);
    
    if (header && content) {
        header.classList.toggle('active');
        content.classList.toggle('show');
        
        const isOpen = header.classList.contains('active');
        localStorage.setItem(`accordion_${accordionId}`, isOpen);
    }
}