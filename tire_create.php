
<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

define('ACCESS', true);
include 'templates/header.php';
?>
<style>
/* Делаем существующие кнопки объемными */
.btn {
    border: 1px solid #0078d7;
    border-bottom: 3px solid #005a9e;
    border-right: 2px solid #005a9e;
    background: #B8860B;
    color: white;
    font-weight: bold;
    cursor: pointer;
    border-radius: 3px;
    box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    padding: 8px 16px;
    transition: all 0.1s ease;
}

.btn:hover {
    background: linear-gradient(to bottom, #106ebe, #005a9e);
    transform: translateY(1px);
}

.btn:active {
    background: linear-gradient(to bottom, #005a9e, #004578);
    transform: translateY(2px);
}
</style>
<div class="main-content">
    <div class="container">
        <h1>⚙️ Создание заказ-наряда шиномонтажа</h1>
        
        <form method="POST" action="tire_create_handler.php">
            <!-- Блок клиента и автомобиля -->
            <div class="form-section">
                <h3>👤 Клиент и автомобиль</h3>
                
                <div class="form-group">
                    <label>Клиент:</label>
                    <select name="client_id" class="form-control" required>
                        <option value="">-- Выберите клиента --</option>
                        <option value="1">Иванов Иван</option>
                        <option value="2">Петров Петр</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Автомобиль:</label>
                    <select name="car_id" class="form-control" required>
                        <option value="">-- Выберите автомобиль --</option>
                        <option value="1">Toyota Camry</option>
                        <option value="2">Honda Civic</option>
                    </select>
                </div>
            </div>

            <!-- Блок шин -->
            <div class="form-section">
                <h3>🛞 Шины</h3>
                
                <div class="tire-positions">
                    <div class="tire-position">
                        <h4>Передняя левая</h4>
                        <input type="text" name="tire_fl_size" placeholder="Размер" class="form-control">
                        <input type="text" name="tire_fl_brand" placeholder="Производитель" class="form-control">
                    </div>
                    
                    <div class="tire-position">
                        <h4>Передняя правая</h4>
                        <input type="text" name="tire_fr_size" placeholder="Размер" class="form-control">
                        <input type="text" name="tire_fr_brand" placeholder="Производитель" class="form-control">
                    </div>
                    
                    <div class="tire-position">
                        <h4>Задняя левая</h4>
                        <input type="text" name="tire_rl_size" placeholder="Размер" class="form-control">
                        <input type="text" name="tire_rl_brand" placeholder="Производитель" class="form-control">
                    </div>
                    
                    <div class="tire-position">
                        <h4>Задняя правая</h4>
                        <input type="text" name="tire_rr_size" placeholder="Размер" class="form-control">
                        <input type="text" name="tire_rr_brand" placeholder="Производитель" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Блок услуг -->
            <div class="form-section">
                <h3>🔧 Услуги</h3>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="services[]" value="mounting"> Монтаж/демонтаж
                    </label>
                    <label>
                        <input type="checkbox" name="services[]" value="balancing"> Балансировка
                    </label>
                    <label>
                        <input type="checkbox" name="services[]" value="alignment"> Развал-схождение
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Примечания:</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <!-- Кнопки -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">✅Создать заказ</button>
                <a href="tire_orders.php" class="btn btn-secondary">❌Отмена</a>
            </div>
			<style>
.tire-positions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 15px 0;
}
.tire-position {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    background: #f9f9f9;
}
.tire-position h4 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}
.form-section {
    background: white;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.form-actions {
    text-align: center;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 4px;
}
</style>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>