<?php
require 'includes/db.php';
session_start();
include 'templates/header.php';
?>

<div class="container mt-4">
    <h2>История выдачи запчастей</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Запчасть</th>
                <th>Артикул</th>
                <th>Количество</th>
                <th>Мастер</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $history = $conn->query("
                SELECT i.issue_date, p.name, p.part_number, i.quantity, i.issued_to 
                FROM issues i
                JOIN parts p ON i.part_id = p.id
                ORDER BY i.issue_date DESC
            ");
            
            while($row = $history->fetch_assoc()):
            ?>
            <tr>
                <td><?= date('d.m.Y H:i', strtotime($row['issue_date'])) ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['part_number'] ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['issued_to'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>