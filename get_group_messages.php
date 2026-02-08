<?php
session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 0;
$groupId = intval($_GET['group_id'] ?? 0);

if ($userId <= 0 || $groupId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier appartenance
$stmt = $pdo->prepare("SELECT can_post, is_admin FROM chat_group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $userId]);
$membership = $stmt->fetch();

if (!$membership) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Récupérer les messages
$sql = "
    SELECT m.*, u.nom, u.prenom, u.profile_pic 
    FROM chat_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.group_id = ?
    ORDER BY m.created_at ASC
    LIMIT 100
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$groupId]);
$messages = $stmt->fetchAll();

$html = '';
foreach ($messages as $msg) {
    $isMe = ($msg['user_id'] == $userId);
    $cls = $isMe ? 'message-out' : 'message-in';
    $name = htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']);
    $pic = !empty($msg['profile_pic']) ? $msg['profile_pic'] : 'assets/default_avatar.png';
    $picUrl = (strpos($pic, 'http') === 0) ? $pic : 'uploads/profiles/' . $pic;
    
    $html .= '<div class="message-bubble ' . $cls . '" style="width: fit-content; max-width: 70%; overflow-wrap: break-word;">';
    
    if (!$isMe) {
        $html .= '<div style="font-size:0.75rem; font-weight:bold; margin-bottom:2px; color:#555;">' . $name . '</div>';
    }

    if (!empty($msg['message'])) {
        $html .= nl2br(htmlspecialchars($msg['message']));
    }
    
    if (!empty($msg['attachment_path'])) {
        $html .= '<div style="margin-top:5px;">';
        if ($msg['attachment_type'] === 'image') {
            $html .= '<img src="' . htmlspecialchars($msg['attachment_path']) . '" style="max-width:200px; border-radius:5px;">';
        } else {
            $html .= '<a href="' . htmlspecialchars($msg['attachment_path']) . '" target="_blank" style="color:inherit; text-decoration:underline;">';
            $html .= '<i class="fas fa-file"></i> Voir le fichier';
            $html .= '</a>';
        }
        $html .= '</div>';
    }
    
    $html .= '<div class="message-time">' . date('H:i', strtotime($msg['created_at'])) . '</div>';
    $html .= '</div>';
}

echo json_encode(['success' => true, 'html' => $html]);
?>