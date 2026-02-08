<?php
session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 0;
$groupId = intval($_GET['group_id'] ?? 0);

if ($userId <= 0 || $groupId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier si membre du groupe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $userId]);
$isMember = $stmt->fetchColumn();

if (!$isMember) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.user_id, m.can_post, m.is_admin, u.nom, u.prenom, u.profile_pic
    FROM chat_group_members m
    JOIN users u ON m.user_id = u.id
    WHERE m.group_id = ?
    ORDER BY u.nom
");
$stmt->execute([$groupId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'members' => $members]);
?>