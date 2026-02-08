<?php
session_start();
require_once 'db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$page = isset($_POST['page_url']) ? $_POST['page_url'] : ($_SERVER['REQUEST_URI'] ?? '/');

if ($page === '') { $page = '/'; }

$stmt = $pdo->prepare("INSERT INTO visits (ip_address, page_url) VALUES (?, ?)");
$stmt->execute([$ip, $page]);

http_response_code(204);
