<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    header('Location: messages.php');
    exit;
}

$me = $_SESSION['user_id'];
$receiverId = intval($_POST['receiver_id'] ?? 0);

if ($receiverId > 0) {
    // Vérifier si une invitation "pending" existe de moi vers cet utilisateur
    $stmt = $pdo->prepare("SELECT id FROM friendships WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->execute([$me, $receiverId]);
    if ($stmt->fetch()) {
        // Supprimer l'invitation
        $stmt = $pdo->prepare("DELETE FROM friendships WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$me, $receiverId]);
    }
}

// Rediriger vers la page précédente ou messages.php
$redirect = $_POST['redirect'] ?? 'messages.php';
header('Location: ' . $redirect);
exit;
