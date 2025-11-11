<?php
$page_title = "Логи системы";
include 'header.php';
?>

<div class="content-container">
    <h1 class="page-title">📊 Логи системы</h1>

    <div class="row-1c">
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>Системные логи</h5>
            </div>
            <div class="card-content">
                <div class="log-filters">
                    <select class="form-control-1c">
                        <option>Все уровни</option>
                        <option>INFO</option>
                        <option>WARNING</option>
                        <option>ERROR</option>
                    </select>
                    <input type="date" class="form-control-1c">
                    <button class="btn-1c">🔍 Фильтровать</button>
                </div>
                
                <div class="log-entries">
                    <div class="log-entry info">
                        <span class="log-time">15.01.2024 10:30:15</span>
                        <span class="log-level">INFO</span>
                        <span class="log-message">Пользователь admin вошел в систему</span>
                    </div>
                    <div class="log-entry warning">
                        <span class="log-time">15.01.2024 09:15:22</span>
                        <span class="log-level">WARNING</span>
                        <span class="log-message">Неудачная попытка входа пользователя test</span>
                    </div>
                    <div class="log-entry error">
                        <span class="log-time">14.01.2024 16:45:10</span>
                        <span class="log-level">ERROR</span>
                        <span class="log-message">Ошибка подключения к базе данных</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.log-filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.log-entry {
    display: grid;
    grid-template-columns: 180px 80px 1fr;
    gap: 1rem;
    padding: 0.5rem;
    border-bottom: 1px solid var(--border-color);
    font-family: monospace;
    font-size: 0.9rem;
}

.log-entry.info { background: #e8f5e8; }
.log-entry.warning { background: #fff3cd; }
.log-entry.error { background: #f8d7da; }

.log-time { color: #666; }
.log-level { font-weight: bold; }
.log-level.info { color: #28a745; }
.log-level.warning { color: #ffc107; }
.log-level.error { color: #dc3545; }
</style>

<?php include 'footer.php'; ?>