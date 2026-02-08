<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: messages.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: messages.php'); exit; }
$uid = $_SESSION['user_id'];
$mid = intval($_POST['message_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
if ($mid>0 && $content !== '') {
    $stmt = $pdo->prepare("UPDATE user_messages SET content = ? WHERE id = ? AND sender_id = ?");
    $stmt->execute([$content, $mid, $uid]);
}
header('Location: messages.php?friend_id='.intval($_POST['friend_id'] ?? 0));
exit;
