<?php
$page_title = "Резервные копии";
include 'header.php';
?>

<div class="content-container">
    <div class="header-actions">
        <h1 class="page-title">💾 Резервные копии</h1>
        <form method="POST" style="display: inline;">
            <button type="submit" name="create_backup" class="btn-1c primary">🔄 Создать backup</button>
        </form>
    </div>

    <div class="row-1c">
        <div class="card-1c">
            <div class="card-header-1c">
                <h5>Создание резервной копии</h5>
            </div>
            <div class="card-content">
                <p>Создайте резервную копию базы данных и файлов системы.</p>
                <ul>
                    <li>База данных: autoservice</li>
                    <li>Файлы конфигурации</li>
                    <li>Загруженные документы</li>
                </ul>
            </div>
        </div>

        <div class="card-1c">
            <div class="card-header-1c">
                <h5>Существующие копии</h5>
            </div>
            <div class="card-content">
                <div class="alert-1c info">
                    📁 Папка с backup: <strong>/backups/</strong>
                </div>
                <div class="backup-list">
                    <div class="backup-item">
                        <span>backup_2024_01_15.sql</span>
                        <span>15.01.2024 14:30</span>
                        <span>2.5 MB</span>
                        <a href="#" class="btn-1c">📥</a>
                    </div>
                    <div class="backup-item">
                        <span>backup_2024_01_14.sql</span>
                        <span>14.01.2024 14:30</span>
                        <span>2.4 MB</span>
                        <a href="#" class="btn-1c">📥</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.backup-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    padding: 0.5rem;
    border-bottom: 1px solid var(--border-color);
    align-items: center;
}
.backup-item:last-child {
    border-bottom: none;
}
</style>

<?php include 'footer.php'; ?>