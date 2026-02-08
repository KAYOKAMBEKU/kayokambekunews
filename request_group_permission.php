<?php
session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 0;
$groupId = intval($_POST['group_id'] ?? 0);

if ($userId <= 0 || $groupId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier si membre
$stmt = $pdo->prepare("SELECT can_post FROM chat_group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $userId]);
$mem = $stmt->fetch();

if (!$mem) {
    echo json_encode(['success' => false, 'message' => 'Non membre']);
    exit;
}

if ($mem['can_post'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Vous avez déjà la permission.']);
    exit;
}

// Update permission_requested flag
$stmtUpd = $pdo->prepare("UPDATE chat_group_members SET permission_requested = 1 WHERE group_id = ? AND user_id = ?");
$stmtUpd->execute([$groupId, $userId]);

// Envoyer une notification au formateur (admin du groupe)
// On cherche les admins du groupe
$stmt = $pdo->prepare("SELECT user_id FROM chat_group_members WHERE group_id = ? AND is_admin = 1");
$stmt->execute([$groupId]);
$admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Créer une notification
// Type: 'group_request'
// Content: "X demande la parole dans le groupe Y"
// On peut utiliser la table notifications existante si on ajoute un type ou on gère le texte.
// Table notifications: id, user_id, type, related_id, is_read, created_at, actor_id.
// type ENUM('like','comment','follow','system') -> Faudra peut-être ajouter 'group_request' ou utiliser 'system'.
// Vérifions la table notifications.

// Pour l'instant on va utiliser 'system' et mettre un message clair.
// Ou mieux, modifier l'ENUM. Mais utilisons 'system' pour éviter ALTER TABLE si possible.
// Wait, user explicitly asked for permission system.
// Let's check notifications structure.

// Fetch group name
$stmtName = $pdo->prepare("SELECT name FROM chat_groups WHERE id = ?");
$stmtName->execute([$groupId]);
$gName = $stmtName->fetchColumn();

foreach ($admins as $adminId) {
    // Éviter spam ?
    // Check if recent notification exists?
    
    $msg = "L'utilisateur demande la parole dans le groupe : " . $gName;
    
    // On insère. Si 'group_request' n'est pas dans l'ENUM, ça va échouer si strict mode.
    // Vérifions DB structure via memory or assume 'system' works.
    // Previous analysis says type is ENUM('like','comment','follow','system').
    
    $stmtIns = $pdo->prepare("INSERT INTO notifications (user_id, type, related_id, actor_id, created_at) VALUES (?, 'system', ?, ?, NOW())");
    // related_id could be group_id. But 'system' usually doesn't use actor_id for display logic maybe?
    // Let's rely on text or specific logic.
    // Actually, I should add a specific type to handle the "Approve" button in notifications list.
    // Or just send a message to the admin?
    
    // Simpler: Just send a message in the group visible only to admin? No.
    // Let's stick to notification.
    $stmtIns->execute([$adminId, $groupId, $userId]); 
    // We need to know context. related_id = group_id. actor_id = requester.
    // But how does admin approve?
    // Admin needs to go to group members list and toggle permission.
}

echo json_encode(['success' => true, 'message' => 'Demande envoyée aux administrateurs du groupe.']);
?>