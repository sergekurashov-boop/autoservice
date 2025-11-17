// Переключение между выбором существующих и созданием новых
document.getElementById('new-client-btn').addEventListener('click', function() {
    document.getElementById('new-client-fields').style.display = 'block';
    document.getElementById('client-select').value = '';
});

// Расчет стоимости
document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', calculateTotal);
});

document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('input', calculateTotal);
});

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('input[name="services[]"]:checked').forEach(checkbox => {
        const serviceId = checkbox.value;
        const price = parseFloat(checkbox.dataset.price);
        const quantity = parseInt(document.querySelector(`input[name="service_quantity[${serviceId}]"]`).value) || 1;
        total += price * quantity;
    });
    document.getElementById('total-price').textContent = total.toLocaleString('ru-RU');
}

// Экспорт в PDF
document.getElementById('export-btn').addEventListener('click', function() {
    // Используем библиотеку jsPDF
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.text('Комплексная запись автосервиса', 20, 20);
    // ... формируем PDF ...
    doc.save('booking_' + new Date().toISOString().slice(0, 10) + '.pdf');
});