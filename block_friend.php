<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: messages.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: messages.php'); exit; }

$blocker = $_SESSION['user_id'];
$blocked = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;
if ($blocked <= 0 || $blocked === $blocker) { header('Location: messages.php'); exit; }

$stmt = $pdo->prepare("INSERT IGNORE INTO blocks (blocker_id, blocked_id) VALUES (?, ?)");
$stmt->execute([$blocker, $blocked]);

header('Location: messages.php?friend_id=' . $blocked);
exit;
