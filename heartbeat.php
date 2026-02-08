<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(204); exit; }
$stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
http_response_code(204);
