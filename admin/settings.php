<?php
$page_title = "Настройки системы";
include 'header.php';
?>

<div class="content-container">
    <h1 class="page-title">⚙️ Настройки системы</h1>

    <div class="row-1c">
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>Основные настройки</h5>
            </div>
            <div class="card-content">
                <form class="settings-form">
                    <div class="form-group">
                        <label>Название автосервиса</label>
                        <input type="text" class="form-control-1c" value="AUTOSERVICE" name="company_name">
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" class="form-control-1c" value="+7 (952) 798-23-29" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control-1c" value="info@autoservice.ru" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label>Время работы</label>
                        <input type="text" class="form-control-1c" value="Пн-Пт: 9:00-18:00" name="work_hours">
                    </div>
                    
                    <button type="submit" class="btn-1c primary">💾 Сохранить настройки</button>
                </form>
            </div>
        </div>

        <div class="card-1c">
            <div class="card-header-1c">
                <h5>Настройки безопасности</h5>
            </div>
            <div class="card-content">
                <div class="security-settings">
                    <div class="setting-item">
                        <span>Автоблокировка при неверном пароле</span>
                        <input type="checkbox" checked>
                    </div>
                    <div class="setting-item">
                        <span>Требовать сложный пароль</span>
                        <input type="checkbox" checked>
                    </div>
                    <div class="setting-item">
                        <span>Сессия (минут)</span>
                        <input type="number" class="form-control-1c" value="60">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-dark);
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.setting-item:last-child {
    border-bottom: none;
}
</style>

<?php include 'footer.php'; ?>