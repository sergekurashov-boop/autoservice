// quick_add.php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Минимальные данные для старта
    $car_data = [
        'license_plate' => $_POST['license_plate'],
        'client_phone' => $_POST['phone']
    ];
    
    // Автоматическое создание клиента "Гость" при необходимости
    // ...
    
    // Создание заказ-наряда
    $order_id = create_quick_order($car_data);
    
    header("Location: order.php?id=$order_id");
}
?>

<form method="post" class="border p-3 bg-light">
    <h4>Быстрый прием авто</h4>
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" name="license_plate" 
                   placeholder="Гос. номер" 
                   class="form-control form-control-lg" 
                   required>
        </div>
        <div class="col-md-6">
            <input type="tel" name="phone" 
                   placeholder="Телефон клиента" 
                   class="form-control form-control-lg">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                <i class="bi bi-plus-circle"></i> Создать заказ-наряд
            </button>
        </div>
    </div>
</form>