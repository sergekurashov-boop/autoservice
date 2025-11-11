<?php
session_start();
echo "Session ID: " . session_id() . "<br>";
$_SESSION['test'] = 'works';
echo "Session test: " . ($_SESSION['test'] ?? 'not set');
?>