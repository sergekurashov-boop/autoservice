<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();
define('ACCESS', true);

$page_title = "Помощь по программе";
$page_description = "Документация системы управления автосервисом";

include 'templates/header.php';
?>

<div class="help-container">
    <div class="help-header">
        <h1 class="page-title"><?= $page_title ?></h1>
        <p class="help-description"><?= $page_description ?></p>
    </div>
    
    <div class="help-content">
        <!-- Контент помощи будет здесь -->
        <?php echo $help_content; ?>
    </div>
    
    <div class="help-navigation">
        <a href="help.php" class="btn-1c">← Назад в помощь</a>
        <div class="help-links">
            <span>Связанные разделы:</span>
            <?php echo $related_links; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>