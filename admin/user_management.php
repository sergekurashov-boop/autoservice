<?php
$page_title = "Управление пользователями";
include 'header.php';
?>

<div class="content-container">
    <div class="header-actions">
        <h1 class="page-title">👨‍💼 Управление пользователями</h1>
        <a href="user_management.php?action=create" class="btn-1c primary">➕ Добавить пользователя</a>
    </div>

    <div class="card-1c">
        <div class="card-header-1c">
            <h5>Список пользователей</h5>
        </div>
        <div class="card-content">
            <table class="table-1c">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя пользователя</th>
                        <th>ФИО</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
                    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><span class="status-badge <?= $user['role'] === 'admin' ? 'primary' : 'info' ?>">
                            <?= $user['role'] === 'admin' ? 'Админ' : 'Пользователь' ?>
                        </span></td>
                        <td><span class="status-badge <?= $user['is_active'] ? 'success' : 'warning' ?>">
                            <?= $user['is_active'] ? 'Активен' : 'Неактивен' ?>
                        </span></td>
                        <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <a href="user_management.php?action=edit&id=<?= $user['id'] ?>" class="btn-1c">✏️</a>
                            <a href="user_management.php?action=delete&id=<?= $user['id'] ?>" class="btn-1c" onclick="return confirm('Удалить пользователя?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>