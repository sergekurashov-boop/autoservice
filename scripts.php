<?php
// scripts.php
?>
<!-- Скрипты -->
<script src="/autoservice/assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Восстановление состояния аккордеонов из localStorage
    function initAccordions() {
        document.querySelectorAll('.accordion-header').forEach(header => {
            const accordionId = header.dataset.accordion;
            const content = document.getElementById(`${accordionId}-menu`);
            const savedState = localStorage.getItem(`accordion_${accordionId}`);
            
            if (savedState === 'true') {
                content.classList.add('show');
                header.classList.add('active');
            }
            
            // Авто-открытие если есть активный пункт
            if (content.querySelector('.sidebar-link.active')) {
                content.classList.add('show');
                header.classList.add('active');
            }
        });
    }
    
    // Инициализация
    initAccordions();
    
    // Обработчик для мобильного меню
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
    });
});

// Функция переключения аккордеона
function toggleAccordion(accordionId) {
    const header = document.querySelector(`[data-accordion="${accordionId}"]`);
    const content = document.getElementById(`${accordionId}-menu`);
    const icon = header.querySelector('.accordion-icon');
    
    content.classList.toggle('show');
    header.classList.toggle('active');
    
    // Сохраняем состояние в localStorage
    localStorage.setItem(`accordion_${accordionId}`, content.classList.contains('show'));
}

// Функция свертывания/развертывания сайдбара
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const toggleIcon = sidebar.querySelector('.sidebar-toggle i');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('collapsed');
    
    if (sidebar.classList.contains('collapsed')) {
        toggleIcon.classList.remove('bi-chevron-left');
        toggleIcon.classList.add('bi-chevron-right');
        mainContent.style.marginLeft = '60px';
    } else {
        toggleIcon.classList.remove('bi-chevron-right');
        toggleIcon.classList.add('bi-chevron-left');
        mainContent.style.marginLeft = '280px';
    }
}
</script>