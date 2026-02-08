<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$senderId = $_SESSION['user_id'];
$receiverId = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$token = $_POST['csrf_token'] ?? '';
if (!csrf_verify($token)) {
    echo json_encode(['success' => false, 'message' => 'Session expirée, veuillez réessayer']);
    exit;
}

$hasAttachment = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0;

if ($receiverId <= 0 || ($content === '' && !$hasAttachment)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides ou message vide']);
    exit;
}

// Vérifier amitié acceptée
$stmt = $pdo->prepare("SELECT 1 FROM friendships 
    WHERE status = 'accepted' AND (
        (sender_id = ? AND receiver_id = ?) OR 
        (sender_id = ? AND receiver_id = ?)
    ) LIMIT 1");
$stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
$isFriend = $stmt->fetchColumn();

if (!$isFriend) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être amis pour envoyer un message']);
    exit;
}

// Vérifier blocages (dans les deux sens)
$stmt = $pdo->prepare("SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1");
$stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
$blocked = $stmt->fetchColumn();
if ($blocked) {
    echo json_encode(['success' => false, 'message' => 'Messaging désactivé (blocage actif).']);
    exit;
}

// Attachment (optional)
$attachmentPath = null;
$attachmentType = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $fname = $_FILES['attachment']['name'];
    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
    $tmp = $_FILES['attachment']['tmp_name'];
    $allowedImg = ['jpg','jpeg','png','gif'];
    $allowedDoc = ['pdf','doc','docx'];
    $allowedAudio = ['mp3','wav','ogg'];
    if (in_array($ext, $allowedImg) || in_array($ext, $allowedDoc) || in_array($ext, $allowedAudio)) {
        $dir = 'uploads/messages';
        if (!is_dir($dir)) { mkdir($dir, 0777, true); }
        $new = 'msg_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . '/' . $new)) {
            $attachmentPath = $dir . '/' . $new;
            $attachmentType = in_array($ext, $allowedImg) ? 'image' : (in_array($ext, $allowedAudio) ? 'audio' : 'document');
        }
    }
}

$stmt = $pdo->prepare("INSERT INTO user_messages (sender_id, receiver_id, content, attachment_path, attachment_type) VALUES (?, ?, ?, ?, ?)");
try {
    $stmt->execute([$senderId, $receiverId, $content, $attachmentPath, $attachmentType]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Message envoyé']);
