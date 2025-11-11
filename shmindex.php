<?php
/* 
 * Этот файл — пример полной страницы для отображения, фильтрации, удаления записей из таблицы `wp_clients`.
 * Вставьте этот код в ваш `index.php` или создайте отдельную страницу шаблона.
 */
// get_header(); // подключение шапки темы
?>

<div style="padding:20px; max-width:800px; margin:auto;">

    <!-- Форма фильтров -->
    <div style="margin-bottom:20px;">
        <label>Фильтр по имени:</label>
        <input type="text" id="nameFilter" />
        <button onclick="applyFilters()">Применить</button>
    </div>

    <!-- Контейнер для списка клиентов -->
    <div id="clients-list" style="border:1px solid #ccc; padding:10px; border-radius:5px;">
        <!-- Здесь будут отображаться записи -->
        <p>Загрузка данных...</p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const listContainer = document.getElementById('clients-list');

    // Функция для получения и отображения данных
    function fetchClients() {
        const nameFilter = document.getElementById('nameFilter').value;
        let url = '<?php echo admin_url('admin-ajax.php'); ?>?action=get_clients';

        if (nameFilter) {
            url += '&name=' + encodeURIComponent(nameFilter);
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                displayClients(data);
                showNotification('Появились новые записи!');
            });
    }

    // Отобразить клиентов
    function displayClients(clients) {
        listContainer.innerHTML = '';
        if (clients.length === 0) {
            listContainer.innerHTML = '<p>Нет данных</p>';
            return;
        }
        clients.forEach(client => {
            const div = document.createElement('div');
            div.innerHTML = `
                <p>ID: ${client.id} | Имя: ${client.name} | Телефон: ${client.phone}
                <button onclick="deleteClient(${client.id})" style="margin-left:10px;">Удалить</button></p>
            `;
            listContainer.appendChild(div);
        });
    }

    // Удаление клиента
    window.deleteClient = function(id) {
        if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=delete_client', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Запись удалена');
                fetchClients(); // Обновить список
            } else {
                alert('Ошибка при удалении');
            }
        });
    };

    // Уведомления
    function showNotification(message) {
        if (Notification.permission === 'granted') {
            new Notification(message);
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification(message);
                }
            });
        }
    }

    // Фильтр
    window.applyFilters = function() {
        fetchClients();
    };

    // Изначальный запуск и автоматическое обновление
    fetchClients();
    setInterval(fetchClients, 10000); // каждые 10 секунд
});
</script>

<?php get_footer(); ?>