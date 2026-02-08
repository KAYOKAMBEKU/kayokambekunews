<?php
session_start();
require_once 'db.php';

$adminId = $_SESSION['user_id'] ?? 0;
$groupId = intval($_POST['group_id'] ?? 0);
$targetUserId = intval($_POST['user_id'] ?? 0);
$canPost = intval($_POST['can_post'] ?? 0);

if ($adminId <= 0 || $groupId <= 0 || $targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier si admin
$stmt = $pdo->prepare("SELECT is_admin FROM chat_group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $adminId]);
$isAdmin = $stmt->fetchColumn();

if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Ne pas modifier les autres admins (ou soi-même) pour éviter blocage
// Sauf si on est le "Super Admin" ? Pour simplifier, un admin ne peut pas muter un autre admin.
$stmt = $pdo->prepare("SELECT is_admin FROM chat_group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $targetUserId]);
$isTargetAdmin = $stmt->fetchColumn();

if ($isTargetAdmin) {
    echo json_encode(['success' => false, 'message' => 'Impossible de modifier un administrateur']);
    exit;
}

$stmt = $pdo->prepare("UPDATE chat_group_members SET can_post = ?, permission_requested = 0 WHERE group_id = ? AND user_id = ?");
if ($stmt->execute([$canPost, $groupId, $targetUserId])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur SQL']);
}
?>