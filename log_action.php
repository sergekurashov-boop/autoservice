<?php
// log_action.php
session_start();
require 'includes/db.php';

if (isset($_GET['action']) && isset($_GET['module'])) {
    $logger->log($_GET['action'], $_GET['module'], $_GET['record_id'] ?? null);
    echo 'OK';
}
?>