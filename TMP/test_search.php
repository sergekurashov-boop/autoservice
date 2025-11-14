<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');
echo json_encode(['test' => 'success', 'message' => 'Search endpoint works']);
exit;