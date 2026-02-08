<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: messages.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: messages.php'); exit; }
$me = $_SESSION['user_id'];
$email = trim($_POST['friend_email'] ?? '');
$targetId = intval($_POST['friend_id'] ?? 0);
if ($targetId <= 0 && $email !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) $targetId = intval($row['id']);
}
if ($targetId > 0 && $targetId != $me) {
    // Si une invitation inverse existe, l'accepter automatiquement
    $stmt = $pdo->prepare("SELECT status FROM friendships WHERE sender_id = ? AND receiver_id = ? LIMIT 1");
    $stmt->execute([$targetId, $me]);
    $inv = $stmt->fetchColumn();
    if ($inv === 'pending') {
        $stmt = $pdo->prepare("UPDATE friendships SET status='accepted' WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$targetId, $me]);
    } else {
        // Vérifier si déjà amis
        $stmt = $pdo->prepare("SELECT 1 FROM friendships WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status='accepted' LIMIT 1");
        $stmt->execute([$me, $targetId, $targetId, $me]);
        $already = $stmt->fetchColumn();
        if (!$already) {
            // Créer une nouvelle invitation
            try {
                $stmt = $pdo->prepare("INSERT INTO friendships (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$me, $targetId]);
            } catch (Exception $e) { /* ignore duplication */ }
        }
    }
}
header('Location: messages.php?friend_id=' . $targetId);
exit;
