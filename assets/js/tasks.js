document.addEventListener('DOMContentLoaded', function() {
    // Открытие модального окна
    document.getElementById('new-task-btn').addEventListener('click', function() {
        document.getElementById('task-modal').style.display = 'block';
        document.getElementById('task-form').reset();
    });
    
    // Закрытие модального окна
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('task-modal').style.display = 'none';
    });
    
    // Перетаскивание задач (drag and drop)
    const taskCards = document.querySelectorAll('.task-card');
    const columns = document.querySelectorAll('.kanban-column');
    
    taskCards.forEach(card => {
        card.addEventListener('dragstart', dragStart);
        card.addEventListener('dragend', dragEnd);
    });
    
    columns.forEach(column => {
        column.addEventListener('dragover', dragOver);
        column.addEventListener('dragenter', dragEnter);
        column.addEventListener('dragleave', dragLeave);
        column.addEventListener('drop', dragDrop);
    });
    
    // Отправка формы
    document.getElementById('task-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('api/save_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        });
    });
    
    // Функции для drag and drop
    function dragStart(e) {
        this.classList.add('dragging');
        e.dataTransfer.setData('text/plain', this.dataset.id);
    }
    
    function dragEnd() {
        this.classList.remove('dragging');
    }
    
    function dragOver(e) {
        e.preventDefault();
    }
    
    function dragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }
    
    function dragLeave() {
        this.classList.remove('drag-over');
    }
    
    function dragDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const taskId = e.dataTransfer.getData('text/plain');
        const newStatus = this.dataset.status;
        const taskElement = document.querySelector(`.task-card[data-id="${taskId}"]`);
        
        // Обновление статуса на сервере
        fetch('api/update_task_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                task_id: taskId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.querySelector('.kanban-column-content').appendChild(taskElement);
            } else {
                alert('Ошибка при обновлении статуса');
            }
        });
    }
});