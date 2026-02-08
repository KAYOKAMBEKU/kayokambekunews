<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];
$friendId = isset($_GET['friend_id']) ? intval($_GET['friend_id']) : 0;

if ($friendId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier amitié acceptée
$stmt = $pdo->prepare("SELECT 1 FROM friendships 
    WHERE status = 'accepted' AND (
        (sender_id = ? AND receiver_id = ?) OR 
        (sender_id = ? AND receiver_id = ?)
    ) LIMIT 1");
$stmt->execute([$userId, $friendId, $friendId, $userId]);
$isFriend = $stmt->fetchColumn();

if (!$isFriend) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être amis pour voir les messages']);
    exit;
}

// Blocage actif ? (dans un sens ou l'autre)
$stmt = $pdo->prepare("SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1");
$stmt->execute([$userId, $friendId, $friendId, $userId]);
$blocked = $stmt->fetchColumn();
if ($blocked) {
    echo json_encode(['success' => false, 'message' => 'Blocage actif entre vous et cet utilisateur.']);
    exit;
}

// Marquer comme lu les messages reçus de cet ami
$upd = $pdo->prepare("UPDATE user_messages SET read_at = NOW() WHERE sender_id = ? AND receiver_id = ? AND read_at IS NULL");
$upd->execute([$friendId, $userId]);

$stmt = $pdo->prepare("SELECT id, sender_id, receiver_id, content, attachment_path, attachment_type, created_at, read_at 
    FROM user_messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC");
$stmt->execute([$userId, $friendId, $friendId, $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);
