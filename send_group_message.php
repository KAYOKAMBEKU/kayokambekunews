<?php
session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 0;
$groupId = intval($_POST['group_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($userId <= 0 || $groupId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier permission d'écrire
$stmt = $pdo->prepare("SELECT can_post FROM chat_group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $userId]);
$membership = $stmt->fetch();

if (!$membership) {
    echo json_encode(['success' => false, 'message' => 'Non membre du groupe']);
    exit;
}

if ($membership['can_post'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission d\'écrire dans ce groupe.']);
    exit;
}

$attachmentPath = null;
$attachmentType = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $allowedImages = ['jpg', 'jpeg', 'png', 'gif'];
    $allowedDocs = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    
    $filename = $_FILES['attachment']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowedImages)) {
        $attachmentType = 'image';
    } elseif (in_array($ext, $allowedDocs)) {
        $attachmentType = 'document';
    } else {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non supporté']);
        exit;
    }
    
    $newFilename = 'group_' . uniqid() . '.' . $ext;
    if (!is_dir('uploads/chat')) {
        mkdir('uploads/chat', 0777, true);
    }
    
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], 'uploads/chat/' . $newFilename)) {
        $attachmentPath = 'uploads/chat/' . $newFilename;
    }
}

if ($content === '' && !$attachmentPath) {
    echo json_encode(['success' => false, 'message' => 'Message vide']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO chat_messages (group_id, user_id, message, attachment_path, attachment_type) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$groupId, $userId, $content, $attachmentPath, $attachmentType])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur SQL']);
}
?>