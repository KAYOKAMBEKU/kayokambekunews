<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: messages.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: messages.php'); exit; }
$me = $_SESSION['user_id'];
$sender = intval($_POST['sender_id'] ?? 0);
$action = $_POST['action'] ?? 'reject';
if ($sender > 0) {
    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE friendships SET status='accepted' WHERE sender_id=? AND receiver_id=? AND status='pending'");
        $stmt->execute([$sender, $me]);
    } else {
        $stmt = $pdo->prepare("UPDATE friendships SET status='rejected' WHERE sender_id=? AND receiver_id=? AND status='pending'");
        $stmt->execute([$sender, $me]);
    }
}
header('Location: messages.php');
exit;
