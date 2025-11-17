<?php
require_once 'db.php';

$services = $conn->query("SELECT id, name FROM services")->fetch_all(MYSQLI_ASSOC);
$mechanics = $conn->query("SELECT id, name FROM mechanics")->fetch_all(MYSQLI_ASSOC);

$selected_service_id = $_POST['service_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_service_id) {
    // Удаляем старые связи
    $stmtDel = $conn->prepare("DELETE FROM service_mechanics WHERE service_id = ?");
    $stmtDel->bind_param("i", $selected_service_id);
    $stmtDel->execute();

    // Вставляем новые связи
    if (!empty($_POST['mechanic_ids'])) {
        $stmtIns = $conn->prepare("INSERT INTO service_mechanics (service_id, mechanic_id) VALUES (?, ?)");
        foreach ($_POST['mechanic_ids'] as $mid) {
            $mid = (int)$mid;
            $stmtIns->bind_param("ii", $selected_service_id, $mid);
            $stmtIns->execute();
        }
    }
    echo "<div class='alert alert-success'>Связи обновлены</div>";
}

// Получаем текущие связи для выбранной услуги
$current_mechanics = [];
if ($selected_service_id) {
    $stmtCur = $conn->prepare("SELECT mechanic_id FROM service_mechanics WHERE service_id = ?");
    $stmtCur->bind_param("i", $selected_service_id);
    $stmtCur->execute();
    $res = $stmtCur->get_result();
    while ($row = $res->fetch_assoc()) {
        $current_mechanics[] = $row['mechanic_id'];
    }
    $stmtCur->close();
}
?>

<form method="post">
    <label for="service_id">Услуга:</label>
    <select name="service_id" id="service_id" onchange="this.form.submit()">
        <option value="">-- Выберите услугу --</option>
        <?php foreach ($services as $service): ?>
            <option value="<?= $service['id'] ?>" <?= ($service['id'] == $selected_service_id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($service['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($selected_service_id): ?>
<form method="post">
    <input type="hidden" name="service_id" value="<?= $selected_service_id ?>">
    <h3>Механики для услуги <?= htmlspecialchars($services[array_search($selected_service_id, array_column($services, 'id'))]['name']) ?></h3>

    <?php foreach ($mechanics as $mechanic): ?>
        <div>
            <label>
                <input type="checkbox" name="mechanic_ids[]" value="<?= $mechanic['id'] ?>"
                    <?= in_array($mechanic['id'], $current_mechanics) ? 'checked' : '' ?>>
                <?= htmlspecialchars($mechanic['name']) ?>
            </label>
        </div>
    <?php endforeach; ?>

    <button type="submit">Сохранить</button>
</form>
<?php endif; ?>