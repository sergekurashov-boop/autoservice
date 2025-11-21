<?php
// backup.php - ИСПРАВЛЕННАЯ ВЕРСИЯ ДЛЯ КОРНЯ ПРОЕКТА
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin']);

ob_start();

// ============================================================================
// ФУНКЦИИ БЭКАПА
// ============================================================================

function createBackupPHP($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $sql = "-- Autoservice Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Структура таблицы
        $sql .= "--\n-- Structure for table `$table`\n--\n";
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_assoc();
        $sql .= $row['Create Table'] . ";\n\n";
        
        // Данные таблицы
        $sql .= "--\n-- Data for table `$table`\n--\n";
        $result = $conn->query("SELECT * FROM `$table`");
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $values = array_map(function($value) use ($conn) {
                    return $value === null ? 'NULL' : "'" . $conn->real_escape_string($value) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function backupConfigFiles($backup_name) {
    $backup_dir = 'backup/';
    
    // Создаем папку backup если её нет
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $zip_path = $backup_dir . $backup_name . '_files.zip';
    
    if (!class_exists('ZipArchive')) {
        return "❌ Класс ZipArchive не доступен";
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
        return "❌ Не могу создать ZIP архив";
    }
    
    // ТЕПЕРЬ МЫ В КОРНЕ - бэкапим текущую папку (autoservice)
    $project_root = __DIR__;
    
    error_log("Backing up PROJECT ROOT: " . $project_root);
    error_log("Backup path: " . $zip_path);

    // Исключаемые папки и файлы
    $excluded = [
        'backup',           // папка с бэкапами
        'node_modules',     // зависимости npm
        'vendor',           // зависимости composer
        '.git',             // git папка
        '.vscode',          // настройки редактора
        'tmp',
        'temp',
        'logs',
        'cache'
    ];
    
    // Рекурсивно добавляем файлы из КОРНЯ ПРОЕКТА
    $files_added = addProjectToZip($zip, $project_root, $excluded);
    
    $zip->close();
    
    error_log("Files added to zip: " . $files_added);
    
    if ($files_added > 0 && file_exists($zip_path)) {
        return $zip_path;
    } else {
        return "❌ Не удалось добавить файлы в архив (добавлено: $files_added)";
    }
}

function addProjectToZip($zip, $folder, $excluded, $parent = '') {
    $files_added = 0;
    
    // Проверяем существует ли папка
    if (!is_dir($folder)) {
        error_log("Directory does not exist: $folder");
        return 0;
    }
    
    $handle = opendir($folder);
    if (!$handle) {
        error_log("Cannot open directory: $folder");
        return 0;
    }
    
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $filepath = $folder . DIRECTORY_SEPARATOR . $file;
            $localpath = $parent . $file;
            
            // Пропускаем исключенные папки
            if (in_array($file, $excluded)) {
                error_log("Skipping excluded: $file");
                continue;
            }
            
            if (is_file($filepath)) {
                // Пропускаем слишком большие файлы (>10MB)
                if (filesize($filepath) > 10 * 1024 * 1024) {
                    error_log("Skipping large file: $filepath");
                    continue;
                }
                
                if ($zip->addFile($filepath, $localpath)) {
                    $files_added++;
                    error_log("Added file: $localpath");
                } else {
                    error_log("Failed to add file: $localpath");
                }
                
            } elseif (is_dir($filepath)) {
                if ($zip->addEmptyDir($localpath)) {
                    error_log("Added directory: $localpath");
                }
                $files_added += addProjectToZip($zip, $filepath, $excluded, $localpath . '/');
            }
        }
    }
    closedir($handle);
    return $files_added;
}

function getBackupSize($filepath) {
    if (!file_exists($filepath)) return '0 KB';
    $size = filesize($filepath);
    if ($size < 1024) return $size . ' B';
    if ($size < 1048576) return round($size / 1024, 2) . ' KB';
    return round($size / 1048576, 2) . ' MB';
}

// ============================================================================
// ОБРАБОТКА ДЕЙСТВИЙ
// ============================================================================

// Удаление бэкапа
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = 'backup/' . $filename;
    
    if (file_exists($filepath) && unlink($filepath)) {
        $_SESSION['success'] = "✅ Бэкап удален: " . $filename;
        if (isset($logger)) $logger->logDelete('backup', 0);
    } else {
        $_SESSION['error'] = "❌ Ошибка удаления бэкапа";
    }
    
    ob_end_clean();
    header("Location: backup.php");
    exit;
}

// Создание бэкапа БАЗЫ ДАННЫХ
if (isset($_POST['create_backup'])) {
    $backup_file = 'backup/autoservice_db_' . date('Y-m-d_H-i-s') . '.sql';
    
    if (!is_dir('backup')) {
        mkdir('backup', 0755, true);
    }
    
    $sql_content = createBackupPHP($conn);
    
    if (file_put_contents($backup_file, $sql_content) !== false) {
        $_SESSION['success'] = "✅ Бэкап БД создан: " . basename($backup_file);
        if (isset($logger)) $logger->logCreate('backup', 0);
    } else {
        $_SESSION['error'] = "❌ Ошибка создания бэкапа БД";
    }
    
    ob_end_clean();
    header("Location: backup.php");
    exit;
}

// Создание ПОЛНОГО СИСТЕМНОГО бэкапа
if (isset($_POST['create_system_backup'])) {
    $timestamp = date('Y-m-d_H-i-s');
    $backup_name = "autoservice_full_{$timestamp}";
    
    if (!is_dir('backup')) {
        mkdir('backup', 0755, true);
    }
    
    $results = [];
    
    // 1. Бэкап базы данных
    $db_file = 'backup/' . $backup_name . '.sql';
    $sql_content = createBackupPHP($conn);
    if (file_put_contents($db_file, $sql_content) !== false) {
        $results[] = "БД: " . basename($db_file);
    }
    
    // 2. Бэкап файловой системы (ТОЛЬКО КОРЕНЬ AUTOSERVICE)
    $files_zip = backupConfigFiles($backup_name);
    
    // Отладочный вывод
    error_log("Backup result: " . (is_string($files_zip) ? $files_zip : "File: " . $files_zip));

    if (is_string($files_zip) && strpos($files_zip, '❌') === 0) {
        // Это сообщение об ошибке
        $results[] = "Файлы: ОШИБКА - " . $files_zip;
    } elseif (file_exists($files_zip)) {
        // Это путь к файлу
        $results[] = "Файлы: " . basename($files_zip) . " (" . getBackupSize($files_zip) . ")";
    } else {
        $results[] = "Файлы: Неизвестная ошибка";
    }
    
    if (!empty($results)) {
        $_SESSION['success'] = "✅ Полный бэкап создан!<br>" . implode("<br>", $results);
        $logger->logCreate('backup_full', 0);
    } else {
        $_SESSION['error'] = "❌ Ошибка создания полного бэкапа";
    }
    
    ob_end_clean();
    header("Location: backup.php");
    exit;
}

// Восстановление из бэкапа
if (isset($_POST['restore_backup']) && isset($_FILES['backup_file'])) {
    if ($_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $file_ext = pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION);
        if ($file_ext !== 'sql') {
            $_SESSION['error'] = "❌ Можно загружать только SQL файлы";
            ob_end_clean();
            header("Location: backup.php");
            exit;
        }
        
        $tmp_file = $_FILES['backup_file']['tmp_name'];
        $sql_content = file_get_contents($tmp_file);
        
        $queries = array_filter(explode(';', $sql_content));
        $success_count = 0;
        $error_count = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if ($conn->query($query)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($error_count === 0) {
            $_SESSION['success'] = "✅ Система восстановлена! Выполнено запросов: $success_count";
            if (isset($logger)) $logger->logUpdate('system_restore', 0);
        } else {
            $_SESSION['error'] = "⚠️ Восстановление с ошибками. Успешно: $success_count, Ошибок: $error_count";
        }
    } else {
        $_SESSION['error'] = "❌ Ошибка загрузки файла";
    }
    
    ob_end_clean();
    header("Location: backup.php");
    exit;
}

// ============================================================================
// ПОЛУЧЕНИЕ СПИСКА БЭКАПОВ
// ============================================================================

$backups = [];
if (is_dir('backup')) {
    $files = scandir('backup');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = 'backup/' . $file;
            $backups[] = [
                'name' => $file,
                'path' => $filepath,
                'size' => getBackupSize($filepath),
                'date' => date('Y-m-d H:i:s', filemtime($filepath)),
                'type' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        }
    }
    
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

define('ACCESS', true);
include 'templates/header.php';
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервные копии</title>
    <link rel="stylesheet" href="admin/assets/css/style.css">
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .backup-list { margin-top: 20px; }
        .backup-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 10px; 
            border-bottom: 1px solid #ddd; 
        }
        .file-size { color: #666; font-size: 0.9em; }
        .file-type { 
            background: #e9ecef; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-size: 0.8em; 
            margin-left: 5px;
        }
        .alert { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .alert-error { background: #ffeaea; color: #dc3545; }
        .alert-success { background: #eaffea; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💾 Резервные копии системы</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="card">
            <h3>🔄 Создание резервных копий</h3>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <h4>База данных</h4>
                    <p>Только данные SQL (быстро)</p>
                    <form method="post">
                        <button type="submit" name="create_backup" class="btn btn-success">
                            📦 Бэкап БД
                        </button>
                    </form>
                </div>
                
                <div style="flex: 1; min-width: 300px;">
                    <h4>Полный системный бэкап</h4>
                    <p>БД + файлы autoservice</p>
                    <form method="post">
                        <button type="submit" name="create_system_backup" class="btn btn-info">
                            🗃️ Полный бэкап системы
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>📥 Восстановление системы</h3>
            <p>Восстановите систему из SQL-бэкапа</p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="backup_file" accept=".sql" required style="margin-bottom: 10px;">
                <br>
                <button type="submit" name="restore_backup" class="btn btn-warning" 
                        onclick="return confirm('⚠️ ВНИМАНИЕ: Это перезапишет ВСЕ текущие данные! Продолжить?')">
                    🔄 Восстановить из SQL
                </button>
            </form>
        </div>

        <!-- Список существующих бэкапов -->
        <div class="backup-list">
            <h3>📋 Существующие бэкапы</h3>
            <?php if (empty($backups)): ?>
                <p>Бэкапов пока нет</p>
            <?php else: ?>
                <?php foreach ($backups as $backup): ?>
                <div class="backup-item">
                    <div>
                        <strong><?= $backup['name'] ?></strong>
                        <span class="file-type"><?= strtoupper($backup['type']) ?></span>
                        <div class="file-size">
                            <?= $backup['size'] ?> • <?= $backup['date'] ?>
                        </div>
                    </div>
                    <div>
                        <a href="<?= $backup['path'] ?>" download class="btn btn-success">📥 Скачать</a>
                        <a href="backup.php?delete=<?= urlencode($backup['name']) ?>" class="btn btn-danger"
                           onclick="return confirm('Удалить бэкап <?= $backup['name'] ?>?')">🗑️ Удалить</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <p><a href="user_management.php">← К пользователям</a></p>
    </div>
    <?php include 'templates/footer.php'; ?>
</body>
</html>