<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$friendId = isset($_GET['friend_id']) ? intval($_GET['friend_id']) : 0;
$groupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// --- 1. AMIS ---
// Récupérer la liste des amis avec le dernier message et le compteur de non-lus
$sqlFriends = "
    SELECT u.id, u.nom, u.prenom, u.profile_pic, u.last_seen,
    (
        SELECT content 
        FROM user_messages 
        WHERE (sender_id = u.id AND receiver_id = :me) OR (sender_id = :me AND receiver_id = u.id) 
        ORDER BY created_at DESC LIMIT 1
    ) as last_msg,
    (
        SELECT created_at 
        FROM user_messages 
        WHERE (sender_id = u.id AND receiver_id = :me) OR (sender_id = :me AND receiver_id = u.id) 
        ORDER BY created_at DESC LIMIT 1
    ) as last_msg_time,
    (
        SELECT COUNT(*) 
        FROM user_messages 
        WHERE sender_id = u.id AND receiver_id = :me AND read_at IS NULL
    ) as unread_count
    FROM users u
    JOIN friendships f ON (
        (f.sender_id = u.id AND f.receiver_id = :me) OR
        (f.receiver_id = u.id AND f.sender_id = :me)
    )
    WHERE f.status = 'accepted' AND u.id != :me
    ORDER BY last_msg_time DESC, u.nom ASC
";

$stmt = $pdo->prepare($sqlFriends);
$stmt->execute([':me' => $userId]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer le statut en ligne (approximatif)
foreach ($friends as $idx => $fr) {
    $last = isset($fr['last_seen']) ? strtotime($fr['last_seen']) : 0;
    $friends[$idx]['is_online'] = ($last && (time() - $last) < 300) ? 1 : 0; // 5 minutes
}

// --- 2. GROUPES DE DISCUSSION ---
$sqlGroups = "
    SELECT g.id, g.name, g.formation_id,
    (
        SELECT message 
        FROM chat_messages 
        WHERE group_id = g.id 
        ORDER BY created_at DESC LIMIT 1
    ) as last_msg,
    (
        SELECT created_at 
        FROM chat_messages 
        WHERE group_id = g.id 
        ORDER BY created_at DESC LIMIT 1
    ) as last_msg_time,
    m.is_admin, m.can_post
    FROM chat_groups g
    JOIN chat_group_members m ON g.id = m.group_id
    WHERE m.user_id = :me
    ORDER BY last_msg_time DESC
";
$stmt = $pdo->prepare($sqlGroups);
$stmt->execute([':me' => $userId]);
$myGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Ami sélectionné
$selectedFriend = null;
$selectedGroup = null;

if ($groupId > 0) {
    foreach ($myGroups as $grp) {
        if ($grp['id'] == $groupId) { $selectedGroup = $grp; break; }
    }
} elseif ($friendId > 0) {
    foreach ($friends as $fr) {
        if ($fr['id'] == $friendId) { $selectedFriend = $fr; break; }
    }
    // Si l'ami n'est pas dans la liste (ex: nouveau chat), le chercher manuellement
    if (!$selectedFriend) {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, profile_pic, last_seen FROM users WHERE id = ?");
        $stmt->execute([$friendId]);
        $selectedFriend = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($selectedFriend) {
            $selectedFriend['is_online'] = (isset($selectedFriend['last_seen']) && (time() - strtotime($selectedFriend['last_seen']) < 300)) ? 1 : 0;
        }
    }
}

// Récupérer les demandes de parole (si admin)
$pendingRequests = [];
if ($selectedGroup && $selectedGroup['is_admin']) {
    $stmtReq = $pdo->prepare("SELECT u.id, u.nom, u.prenom FROM chat_group_members m JOIN users u ON m.user_id = u.id WHERE m.group_id = ? AND m.permission_requested = 1 AND m.can_post = 0");
    $stmtReq->execute([$selectedGroup['id']]);
    $pendingRequests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles spécifiques pour la messagerie type "App" */
        body {
            background-color: #f0f2f5;
            height: 100vh;
            overflow: hidden; /* Pour fixer le layout */
            display: flex;
            flex-direction: column;
        }
        
        .chat-container {
            display: flex;
            height: calc(100vh - 70px); /* Hauteur moins header */
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative; /* Pour le sidebar absolute */
            overflow: hidden; /* Cache le sidebar quand il est hors champ */
        }

        /* Sidebar Gauch (Liste Amis) */
        .chat-sidebar {
            width: 350px;
            border-right: 1px solid #e1e1e1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .sidebar-header {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e1e1e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .friend-list {
            flex: 1;
            overflow-y: auto;
        }

        .friend-item {
            display: flex;
            padding: 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .friend-item:hover, .friend-item.active {
            background: #f0f2f5;
        }

        .friend-avatar {
            position: relative;
            margin-right: 15px;
        }

        .friend-avatar img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .online-status {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #28a745;
            border: 2px solid #fff;
            border-radius: 50%;
        }

        .friend-info {
            flex: 1;
            overflow: hidden;
        }

        .friend-name {
            font-weight: 600;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .last-message {
            color: #666;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            background: var(--primary-green);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            min-width: 18px;
            text-align: center;
        }

        /* Zone de Chat (Droite) */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), #efe7dd url('img/logo.jpg') repeat;
            background-size: 100px; /* Logo pattern */
        }

        .chat-header {
            padding: 10px 20px;
            background: #fff;
            border-bottom: 1px solid #e1e1e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message-bubble {
            width: fit-content;
            max-width: 70%;
            padding: 8px 12px;
            border-radius: 12px;
            position: relative;
            font-size: 0.95rem;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            word-wrap: break-word; /* Old syntax */
            overflow-wrap: break-word; /* Modern syntax */
            word-break: break-word; /* Ensure long words break */
            white-space: pre-wrap; /* Preserve newlines */
            text-align: left; /* Force alignment à gauche pour le texte */
        }

        .message-bubble.received {
            background: #fff;
            align-self: flex-start;
            border-top-left-radius: 0;
        }

        .message-bubble.sent {
            background: #dcf8c6;
            align-self: flex-end;
            border-top-right-radius: 0;
        }

        .message-time {
            font-size: 0.7rem;
            color: #999;
            text-align: right;
            margin-top: 4px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 4px;
        }

        .read-receipt {
            color: #34b7f1; /* Bleu WhatsApp pour "Lu" */
        }

        .chat-input-area {
            padding: 15px;
            background: #f0f0f0;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 20px;
            outline: none;
            font-size: 1rem;
        }

        .btn-send {
            background: var(--primary-green);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: transform 0.2s;
        }

        .btn-send:hover {
            transform: scale(1.1);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #888;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-container {
                position: relative;
                height: calc(100vh - 60px);
            }
            .chat-sidebar {
                width: 100%;
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 10;
                display: flex; /* Default show sidebar */
            }
            .chat-area {
                width: 100%;
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 20;
                background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), #efe7dd url('img/logo.jpg') repeat;
                background-size: 100px;
                display: none;
            }

            /* Toggle based on parent class */
            .chat-container.chat-active .chat-sidebar {
                display: none;
            }
            .chat-container.chat-active .chat-area {
                display: flex;
            }

            .chat-header {
                padding: 10px;
            }
            
            .back-btn {
                display: inline-block !important;
                margin-right: 10px;
                color: var(--brand);
                font-size: 1.2rem;
                text-decoration: none;
            }

            /* Adjust bubbles for mobile */
            .message-bubble {
                max-width: 85%;
                font-size: 0.9rem;
            }
        }
        @media (min-width: 769px) {
            .back-btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="chat-container <?php echo ($selectedFriend || $selectedGroup) ? 'chat-active' : ''; ?>">
        <!-- Liste des amis -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h3>Discussions</h3>
                <div style="display:flex; gap:10px;">
                     <!-- Bouton Invitations -->
                     <button onclick="document.getElementById('invitationsModal').style.display='block'" class="btn-small" style="background:#e4e6eb; color:#000;">
                        <i class="fas fa-user-plus"></i>
                     </button>
                </div>
            </div>
            
            <div class="friend-list">
                <?php if (!empty($myGroups)): ?>
                <div style="padding: 10px; font-weight:bold; background:#eee;">Groupes</div>
                <?php foreach ($myGroups as $grp): ?>
                    <a href="messages.php?group_id=<?php echo $grp['id']; ?>" class="friend-item <?php echo ($groupId == $grp['id']) ? 'active' : ''; ?>">
                        <div class="friend-avatar">
                            <div style="width:50px; height:50px; border-radius:50%; background:#007bff; color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="friend-info">
                            <div class="friend-name">
                                <?php echo htmlspecialchars($grp['name']); ?>
                            </div>
                            <div class="last-message">
                                <?php echo htmlspecialchars($grp['last_msg'] ?? 'Aucun message'); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php endif; ?>

                <div style="padding: 10px; font-weight:bold; background:#eee;">Amis</div>

                <?php if (empty($friends)): ?>
                    <div style="padding:20px; text-align:center; color:#888;">
                        Aucun ami pour le moment.
                        <br><br>
                        <button onclick="document.getElementById('invitationsModal').style.display='block'" class="btn-outline">Inviter des amis</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($friends as $fr): ?>
                        <a href="messages.php?friend_id=<?php echo $fr['id']; ?>" class="friend-item <?php echo ($selectedFriend && $selectedFriend['id'] == $fr['id']) ? 'active' : ''; ?>">
                            <div class="friend-avatar">
                                <img src="<?php echo $fr['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/50' : 'uploads/profiles/'.$fr['profile_pic']; ?>" alt="Avatar">
                                <?php if ($fr['is_online']): ?>
                                    <div class="online-status"></div>
                                <?php endif; ?>
                            </div>
                            <div class="friend-info">
                                <div class="friend-name">
                                    <span><?php echo htmlspecialchars($fr['prenom'] . ' ' . $fr['nom']); ?></span>
                                    <?php if ($fr['last_msg_time']): ?>
                                        <small style="font-weight:normal; color:#999; font-size:0.7rem;">
                                            <?php echo date('H:i', strtotime($fr['last_msg_time'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <div class="last-message">
                                        <?php echo htmlspecialchars($fr['last_msg'] ?? 'Aucun message'); ?>
                                    </div>
                                    <?php if ($fr['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?php echo $fr['unread_count']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Zone de Chat -->
        <div class="chat-area">
            <?php if ($selectedGroup): ?>
                <!-- HEADER GROUPE -->
                <div class="chat-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <a href="messages.php" class="back-btn" style="color:#333; font-size:1.2rem; margin-right:5px;"><i class="fas fa-arrow-left"></i></a>
                        <div style="width:40px; height:40px; border-radius:50%; background:#007bff; color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($selectedGroup['name']); ?></div>
                            <div style="font-size:0.8rem; color:#888;">Groupe de discussion</div>
                        </div>
                    </div>
                    <button onclick="toggleGroupSidebar()" class="btn-outline" style="border:none; background:#e4e6eb; color:#000; height:40px; padding:0 15px; display:inline-flex; align-items:center; border-radius:20px;">
                        <i class="fas fa-users" style="margin-right:5px;"></i> Membres
                    </button>
                </div>

                <!-- ALERTE DEMANDES DE PAROLE -->
                <?php if (!empty($pendingRequests)): ?>
                <div style="background:#fff3cd; color:#856404; padding:10px 20px; border-bottom:1px solid #ffeeba; display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <i class="fas fa-hand-paper"></i> <strong>Demandes de parole :</strong>
                        <?php foreach($pendingRequests as $req): ?>
                            <span style="background:#fff; padding:2px 8px; border-radius:12px; border:1px solid #ddd; display:inline-flex; align-items:center;">
                                <?php echo htmlspecialchars($req['prenom'].' '.$req['nom']); ?>
                                <button onclick="approveUser(<?php echo $req['id']; ?>)" class="btn-small" style="margin-left:5px; background:#28a745; color:#fff; border:none; border-radius:50%; width:24px; height:24px; cursor:pointer;"><i class="fas fa-check" style="font-size:10px;"></i></button>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- MESSAGES GROUPE -->
                <div class="chat-messages" id="messagesList">
                    <div style="text-align:center; padding:20px;">Chargement des messages du groupe...</div>
                </div>

                <!-- INPUT GROUPE -->
                <div class="chat-input-area" style="flex-direction: column; align-items: stretch;">
                    <?php if ($selectedGroup['can_post']): ?>
                        <div style="display:flex; align-items:center; width:100%;">
                            <label for="group_attachment" style="cursor:pointer; color:#666; font-size:1.2rem; padding:5px;">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="group_attachment" style="display:none;" onchange="handleFileSelect(this)">
                            
                            <textarea id="messageInput" class="chat-input" placeholder="Écrivez un message..." rows="1" style="resize:none; overflow-y:hidden; font-family:inherit; min-height:45px; flex:1; margin:0 10px;"></textarea>
                            
                            <button class="btn-send" onclick="sendGroupMessage()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="file-preview" style="display:none; padding: 5px 10px; font-size: 0.85rem; color: #555;"></div>
                    <?php else: ?>
                        <div style="text-align:center; width:100%; padding: 10px;">
                            <span style="color:#666; margin-right:10px;">Seuls les formateurs peuvent écrire.</span>
                            <button id="btnRequestPerm" class="btn-submit" style="width:auto; padding:5px 15px; font-size:0.9rem;" onclick="requestPermission(<?php echo $selectedGroup['id']; ?>)">Demander la parole</button>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                const currentGroupId = <?php echo $selectedGroup['id']; ?>;
                const currentUserId = <?php echo $userId; ?>;
                
                function handleFileSelect(input) {
                    const preview = document.getElementById('file-preview');
                    if (input.files && input.files[0]) {
                        preview.textContent = "Fichier sélectionné : " + input.files[0].name;
                        preview.style.display = 'block';
                    } else {
                        preview.style.display = 'none';
                    }
                }

                // Fonction pour charger les messages du groupe
                function loadGroupMessages() {
                    fetch(`get_group_messages.php?group_id=${currentGroupId}`)
                    .then(r => r.json())
                    .then(data => {
                        const list = document.getElementById('messagesList');
                        if(data.success) {
                            // On compare pour éviter le refresh inutile ? Pour l'instant on remplace tout
                            // Idéalement on append, mais simple pour commencer
                            list.innerHTML = data.html;
                            // Scroll en bas seulement si on était déjà en bas ou au chargement initial
                            // list.scrollTop = list.scrollHeight; 
                        }
                    });
                }

                // Fonction pour envoyer un message de groupe
                function sendGroupMessage() {
                    const input = document.getElementById('messageInput');
                    const content = input.value.trim();
                    const fileInput = document.getElementById('group_attachment');
                    const file = fileInput.files[0];

                    if (!content && !file) return;

                    const formData = new FormData();
                    formData.append('group_id', currentGroupId);
                    formData.append('content', content);
                    if (file) {
                        formData.append('attachment', file);
                    }

                    fetch('send_group_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            input.value = '';
                            fileInput.value = '';
                            document.getElementById('file-preview').style.display = 'none';
                            loadGroupMessages();
                        } else {
                            alert('Erreur: ' + data.message);
                        }
                    });
                }

                function requestPermission(gid) {
                    if(confirm("Voulez-vous demander la permission de parler au formateur ?")) {
                        fetch('request_group_permission.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `group_id=${gid}`
                        })
                        .then(r => r.json())
                        .then(data => {
                            alert(data.message);
                            if(data.success) {
                                document.getElementById('btnRequestPerm').innerText = "Demande envoyée";
                                document.getElementById('btnRequestPerm').disabled = true;
                            }
                        });
                    }
                }

                // Charger au démarrage et toutes les 3s
                loadGroupMessages();
                setInterval(loadGroupMessages, 3000);
                </script>

                <div id="drawerOverlay" style="display:none; position:absolute; inset:0; background: rgba(0,0,0,0.4); z-index: 90;"></div>
                <div id="groupInfoDrawer" class="chat-info-sidebar">
                    <div class="chat-info-header">
                        <span>Membres du Groupe</span>
                        <button onclick="toggleGroupSidebar()" class="btn-outline" style="border:none; background:#e4e6eb; color:#000; height:32px; padding:0 12px; display:inline-flex; align-items:center; border-radius:16px;">Fermer</button>
                    </div>
                    <div class="chat-info-content" id="membersList">
                        Chargement...
                    </div>
                </div>
                <script>
                const isGroupAdmin = <?php echo $selectedGroup['is_admin'] ? 'true' : 'false'; ?>;
                function toggleGroupSidebar() {
                    const el = document.getElementById('groupInfoDrawer');
                    const overlay = document.getElementById('drawerOverlay');
                    const open = !el.classList.contains('open');
                    if (open) {
                        el.classList.add('open');
                        overlay.style.display = 'block';
                        loadMembers();
                    } else {
                        el.classList.remove('open');
                        overlay.style.display = 'none';
                    }
                }
                document.getElementById('drawerOverlay').addEventListener('click', toggleGroupSidebar);
                
                function loadMembers() {
                    fetch('get_group_members.php?group_id=<?php echo $selectedGroup['id']; ?>')
                    .then(r => r.json())
                    .then(data => {
                        const div = document.getElementById('membersList');
                        if (data.success) {
                            let html = '';
                            data.members.forEach(m => {
                                let actions = '';
                                if (isGroupAdmin && m.user_id != currentUserId) {
                                    if (m.can_post == 1) {
                                        actions = `<button onclick="togglePerm(${m.user_id}, 0)" class="btn-outline" style="font-size:0.8rem; padding:2px 8px; color:red; border-color:red;">Mute</button>`;
                                    } else {
                                        actions = `<button onclick="togglePerm(${m.user_id}, 1)" class="btn-outline" style="font-size:0.8rem; padding:2px 8px; color:green; border-color:green;">Autoriser</button>`;
                                    }
                                }
                                
                                html += `<div style="display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px solid #eee;">
                                    <div style="display:flex; align-items:center;">
                                        <img src="${m.profile_pic ? (m.profile_pic.startsWith('http') ? m.profile_pic : 'uploads/profiles/'+m.profile_pic) : 'assets/default_avatar.png'}" style="width:30px; height:30px; border-radius:50%; margin-right:10px;">
                                        <span>${m.prenom} ${m.nom} ${m.is_admin ? '<i class="fas fa-crown" style="color:gold; margin-left:5px;"></i>' : ''}</span>
                                    </div>
                                    <div>
                                        ${actions}
                                    </div>
                                </div>`;
                            });
                            div.innerHTML = html;
                        }
                    });
                }
                function togglePerm(uid, val) {
                    if (!isGroupAdmin) return;
                    fetch('update_group_permission.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `group_id=<?php echo $selectedGroup['id']; ?>&user_id=${uid}&can_post=${val}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) loadMembers();
                        else alert(data.message);
                    });
                }
                // Charger à l'ouverture (ou ici direct)
                loadMembers();
                </script>

            <?php elseif ($selectedFriend): ?>
                <div class="chat-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <a href="messages.php" class="back-btn" style="color:#333; font-size:1.2rem; margin-right:5px;"><i class="fas fa-arrow-left"></i></a>
                        <img src="<?php echo $selectedFriend['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/40' : 'uploads/profiles/'.$selectedFriend['profile_pic']; ?>" alt="Avatar" style="width:40px; height:40px; border-radius:50%;">
                        <div>
                            <div style="font-weight:600;"><a href="user_profile.php?id=<?php echo $selectedFriend['id']; ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($selectedFriend['prenom'] . ' ' . $selectedFriend['nom']); ?></a></div>
                            <div style="font-size:0.8rem; color:<?php echo $selectedFriend['is_online'] ? '#28a745' : '#888'; ?>;">
                                <?php echo $selectedFriend['is_online'] ? 'En ligne' : 'Hors ligne'; ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="https://meet.jit.si/KAYOKA-<?php echo $userId; ?>-<?php echo $selectedFriend['id']; ?>#config.startWithVideoMuted=true" target="_blank" class="btn-outline" style="border:none; background:#e4e6eb; color:#000; width:40px; height:40px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:50%;">
                            <i class="fas fa-phone-alt"></i>
                        </a>
                        <a href="user_profile.php?id=<?php echo $selectedFriend['id']; ?>" class="btn-outline" style="border:none; background:#e4e6eb; color:#000; width:40px; height:40px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:50%;">
                            <i class="fas fa-info"></i>
                        </a>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div style="text-align:center; padding:20px;">Chargement...</div>
                </div>

                <form class="chat-input-area" id="chatForm" enctype="multipart/form-data">
                    <input type="hidden" name="receiver_id" value="<?php echo $selectedFriend['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <label for="attachment" style="cursor:pointer; color:#666; font-size:1.2rem; padding:5px;">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <input type="file" id="attachment" name="attachment" style="display:none;">
                    
                    <textarea name="content" id="chatInput" class="chat-input" placeholder="Écrivez un message..." required rows="1" style="resize:none; overflow-y:hidden; font-family:inherit; min-height:45px;"></textarea>
                    
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Vos messages</h3>
                    <p>Sélectionnez une conversation pour commencer à discuter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Invitations -->
    <div id="invitationsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:#fff; max-width:500px; margin:50px auto; border-radius:8px; overflow:hidden;">
            <div style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <h3>Gérer les amis</h3>
                <button onclick="document.getElementById('invitationsModal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
            </div>
            <div style="padding:20px; max-height:400px; overflow-y:auto;">
                <!-- Invitations reçues -->
                <?php
                    $stmt = $pdo->prepare("SELECT f.sender_id, u.prenom, u.nom FROM friendships f JOIN users u ON u.id = f.sender_id WHERE f.receiver_id = ? AND f.status = 'pending'");
                    $stmt->execute([$userId]);
                    $invites = $stmt->fetchAll();
                ?>
                <?php if (!empty($invites)): ?>
                    <h4>Invitations reçues</h4>
                    <?php foreach ($invites as $iv): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #f0f0f0;">
                            <span><?php echo htmlspecialchars($iv['prenom'].' '.$iv['nom']); ?></span>
                            <form action="respond_invite.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="sender_id" value="<?php echo $iv['sender_id']; ?>">
                                <button type="submit" name="action" value="accept" class="btn-small" style="background:#28a745;">Accepter</button>
                                <button type="submit" name="action" value="reject" class="btn-small" style="background:#dc3545;" onclick="return confirm('Refuser cette invitation ?');">Refuser</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Recherche -->
                <h4 style="margin-top:20px;">Ajouter un ami</h4>
                <input type="text" id="modalSearch" placeholder="Rechercher par nom..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <div id="modalSearchResults" style="margin-top:10px;"></div>
            </div>
        </div>
    </div>

    <script>
    <?php if ($selectedFriend): ?>
    const friendId = <?php echo $selectedFriend['id']; ?>;
    const myId = <?php echo $userId; ?>;
    const chatBox = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');

    if(chatInput){
        // Supprimé : la gestion automatique de Entrée pour l'envoi
        // L'utilisateur doit cliquer sur le bouton envoyer
        // Le comportement par défaut (saut de ligne) est conservé pour Entrée

        chatInput.addEventListener('input', function() {
             this.style.height = 'auto';
             this.style.height = (Math.min(this.scrollHeight, 120)) + 'px'; // Max height 120px
             if(this.value === '') this.style.height = '45px'; // Reset if empty
        });
    }

    function escapeHtml(text) {
        if (!text) return text;
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function loadMessages() {
        fetch('fetch_messages.php?friend_id=' + friendId)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            
            chatBox.innerHTML = '';
            
            if (data.messages.length === 0) {
                chatBox.innerHTML = '<div style="text-align:center; color:#888; margin-top:20px;">Dites bonjour ! 👋</div>';
                return;
            }

            let lastDate = null;

            data.messages.forEach(m => {
                const isMe = m.sender_id == myId;
                const date = new Date(m.created_at);
                const time = date.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                
                const div = document.createElement('div');
                div.className = 'message-bubble ' + (isMe ? 'sent' : 'received');
                
                let content = escapeHtml(m.content);
                
                if (m.attachment_path) {
                    if (m.attachment_type === 'image') {
                        content += `<div style="margin-top:5px;"><img src="${escapeHtml(m.attachment_path)}" style="max-width:200px; border-radius:5px;"></div>`;
                    } else {
                        content += `<div style="margin-top:5px;"><a href="${escapeHtml(m.attachment_path)}" target="_blank" style="color:var(--primary-green);">📎 Voir la pièce jointe</a></div>`;
                    }
                }

                // Status de lecture pour mes messages
                let readStatus = '';
                if (isMe) {
                    if (m.read_at) {
                        readStatus = '<i class="fas fa-check-double read-receipt"></i>'; // Bleu
                    } else {
                        readStatus = '<i class="fas fa-check"></i>'; // Gris
                    }
                }

                div.innerHTML = `
                    ${content}
                    <div class="message-time">
                        ${time}
                        ${readStatus}
                    </div>
                `;
                
                chatBox.appendChild(div);
            });
            
            // Scroll to bottom
            chatBox.scrollTop = chatBox.scrollHeight;
        });
    }

    // Charger les messages initialement
    loadMessages();
    
    // Auto-refresh toutes les 3s
    setInterval(loadMessages, 3000);

    // Envoi de message
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('send_message.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.reset();
                loadMessages();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    });
    <?php endif; ?>

    // Recherche d'amis dans le modal
    document.getElementById('modalSearch')?.addEventListener('input', function() {
        const q = this.value;
        if (q.length < 2) return;
        
        fetch('search_users.php?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('modalSearchResults');
            container.innerHTML = '';
            if (data.success && data.results) {
                data.results.forEach(u => {
                    const div = document.createElement('div');
                    div.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;';
                    div.innerHTML = `
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="${u.profile_pic == 'default.png' ? 'https://via.placeholder.com/30' : 'uploads/profiles/'+u.profile_pic}" style="width:30px; height:30px; border-radius:50%;">
                            <span>${u.prenom} ${u.nom}</span>
                        </div>
                        <form action="send_invite.php" method="POST">
                             <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                             <input type="hidden" name="friend_id" value="${u.id}">
                             <button class="btn-outline" style="padding:2px 8px; font-size:0.8rem;">Ajouter</button>
                        </form>
                    `;
                    container.appendChild(div);
                });
            }
        });
    });
    </script>
    <script src="js/script.js"></script>
    <script>
    // Mobile nav toggle (copié du footer pour la page messages qui n'a pas de footer)
    try {
      document.body.classList.add('has-js');
      const headerEl = document.querySelector('header');
      const toggleBtn = document.querySelector('.menu-toggle');
      if (toggleBtn && headerEl) {
        toggleBtn.setAttribute('type','button');
        toggleBtn.addEventListener('click', function(){
          const isOpen = document.body.classList.toggle('nav-open');
          toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
      }
    } catch(e) {}
    </script>
</body>
</html>
