// Добавить в assets/js/app.js
document.querySelectorAll('.accordion-button').forEach(button => {
    button.addEventListener('click', () => {
        const collapse = button.nextElementSibling;
        collapse.style.display = collapse.style.display === 'block' ? 'none' : 'block';
    });
});