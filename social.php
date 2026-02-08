<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

// Si pas connecté, redirection vers login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$settings = ['social_bg'=>null];
$st = $pdo->prepare("SELECT social_bg FROM user_settings WHERE user_id=?");
$st->execute([$userId]);
$rowSet = $st->fetch(PDO::FETCH_ASSOC);
if ($rowSet) { $settings['social_bg'] = $rowSet['social_bg']; }

// Récupérer les domaines pour le filtre
$stmt = $pdo->query("SELECT * FROM domains ORDER BY name");
$domains = $stmt->fetchAll();

// 1. Récupérer les Posts avec Filtres
$whereClause = "WHERE p.status = 'approved'";
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $whereClause = "WHERE (p.status = 'approved' OR p.status = 'pending')";
}
$params = [$userId];

if (!empty($_GET['domain'])) {
    $whereClause .= " AND p.domain_id = ?";
    $params[] = $_GET['domain'];
}

if (!empty($_GET['media_type'])) {
    $whereClause .= " AND p.media_type = ?";
    $params[] = $_GET['media_type'];
}

// Recherche textuelle (Titre ou Contenu)
if (!empty($_GET['q'])) {
    $whereClause .= " AND (p.content LIKE ? OR p.title LIKE ?)";
    $params[] = '%' . $_GET['q'] . '%';
    $params[] = '%' . $_GET['q'] . '%';
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*, u.nom, u.prenom, u.profile_pic, d.name as domain_name,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN domains d ON p.domain_id = d.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// 2. Récupérer les News (À la une - Droite)
$stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 5");
$newsList = $stmt->fetchAll();

// 3. Suggestions d'amis (Utilisateurs qui ne sont PAS encore amis)
// Exclut soi-même et ceux avec qui il y a déjà une relation (pending/accepted)
$sqlFriends = "SELECT * FROM users WHERE id != ? AND id NOT IN (
    SELECT receiver_id FROM friendships WHERE sender_id = ?
    UNION
    SELECT sender_id FROM friendships WHERE receiver_id = ?
) LIMIT 5";
$stmt = $pdo->prepare($sqlFriends);
$stmt->execute([$userId, $userId, $userId]);
$suggestions = $stmt->fetchAll();

// Traitement Like via POST (fallback si non-JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!csrf_verify($token)) { header('Location: social.php'); exit; }
    $postId = intval($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$userId, $postId]);
            
            // Notification
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $ownerId = $stmt->fetchColumn();
            if ($ownerId && $ownerId != $userId) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?, ?, 'like', ?)");
                $stmt->execute([$ownerId, $userId, $postId]);
            }
        } catch (Exception $e) {
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);
        }
    }
    header('Location: social.php');
    exit;
}

// Traitement Invitation Ami
if (isset($_GET['add_friend'])) {
    $friendId = $_GET['add_friend'];
    try {
        $stmt = $pdo->prepare("INSERT INTO friendships (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$userId, $friendId]);
    } catch (Exception $e) {
        // Déjà invité
    }
    header('Location: social.php');
    exit;
}

// Traitement Réponse Invitation
if (isset($_GET['accept_friend'])) {
    $senderId = $_GET['accept_friend'];
    $stmt = $pdo->prepare("UPDATE friendships SET status = 'accepted' WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$senderId, $userId]);
    header('Location: social.php');
    exit;
}

if (isset($_GET['reject_friend'])) {
    $senderId = $_GET['reject_friend'];
    $stmt = $pdo->prepare("DELETE FROM friendships WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$senderId, $userId]);
    header('Location: social.php');
    exit;
}

// Traitement Suppression Post (Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && $_SESSION['user_role'] === 'admin') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: social.php'); exit; }
    $postId = intval($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $img = $stmt->fetchColumn();
        if ($img && strpos($img, 'uploads/posts/') === 0 && file_exists($img)) { @unlink($img); }
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
    }
    header('Location: social.php');
    exit;
}

// Traitement Approbation Post (Admin depuis le fil)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_post']) && $_SESSION['user_role'] === 'admin') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: social.php'); exit; }
    $postId = intval($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        $stmt = $pdo->prepare("UPDATE posts SET status = 'approved' WHERE id = ?");
        $stmt->execute([$postId]);
    }
    header('Location: social.php');
    exit;
}

// 4. Récupérer les demandes d'amis en attente
$sqlRequests = "SELECT u.* FROM users u 
                JOIN friendships f ON u.id = f.sender_id 
                WHERE f.receiver_id = ? AND f.status = 'pending'";
$stmt = $pdo->prepare($sqlRequests);
$stmt->execute([$userId]);
$friendRequests = $stmt->fetchAll();

// 5. Récupérer les invitations envoyées (en attente)
$sqlSent = "SELECT u.id, u.prenom, u.nom, u.profile_pic FROM users u 
            JOIN friendships f ON u.id = f.receiver_id 
            WHERE f.sender_id = ? AND f.status = 'pending'";
$stmt = $pdo->prepare($sqlSent);
$stmt->execute([$userId]);
$sentRequests = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fil Social - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container social-layout <?php
        echo $settings['social_bg']==='forest'?'bg-forest':
             ($settings['social_bg']==='ocean'?'bg-ocean':
             ($settings['social_bg']==='sunset'?'bg-sunset':
             ($settings['social_bg']==='photo1'?'bg-photo-1':
             ($settings['social_bg']==='photo2'?'bg-photo-2':''))));
    ?>">
        
        <!-- COLONNE GAUCHE : FIL D'ACTUALITÉ -->
        <div class="feed-column">
            <h2 class="section-title"><i class="fas fa-stream"></i> Fil d'Actualité</h2>
            
            <!-- Recherche et Filtres -->
            <div class="card" style="padding: 1rem; margin-bottom: 1rem;">
                <form action="" method="GET" style="display: grid; gap: 10px;">
                    <input type="text" name="q" placeholder="Rechercher des vidéos, livres, articles..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    
                    <div class="grid-2" style="gap: 10px;">
                        <select name="domain" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Tous les domaines</option>
                            <?php foreach ($domains as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php if(isset($_GET['domain']) && $_GET['domain'] == $d['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="media_type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Tous les types</option>
                            <option value="video" <?php if(isset($_GET['media_type']) && $_GET['media_type'] == 'video') echo 'selected'; ?>>Vidéos</option>
                            <option value="audio" <?php if(isset($_GET['media_type']) && $_GET['media_type'] == 'audio') echo 'selected'; ?>>Audios</option>
                            <option value="document" <?php if(isset($_GET['media_type']) && $_GET['media_type'] == 'document') echo 'selected'; ?>>Livres / Documents</option>
                            <option value="image" <?php if(isset($_GET['media_type']) && $_GET['media_type'] == 'image') echo 'selected'; ?>>Images</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" style="width: 100%; padding: 0.5rem;"><i class="fas fa-search"></i> Filtrer</button>
                </form>
            </div>

            <!-- Lien rapide pour publier -->
            <div class="card" style="text-align: center; padding: 1rem;">
                <p>Exprimez-vous, partagez vos idées !</p>
                <a href="dashboard.php" class="btn-submit" style="display: inline-block; width: auto; text-decoration: none; margin-top: 10px;">Créer une publication</a>
            </div>

            <?php foreach ($posts as $post): ?>
            <div class="card">
                <div class="post-header" style="justify-content: space-between; align-items: flex-start;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="user_profile.php?id=<?php echo intval($post['user_id']); ?>">
                            <img src="<?php echo $post['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/40' : 'uploads/profiles/'.$post['profile_pic']; ?>" alt="User" class="profile-pic-small">
                        </a>
                        <div>
                            <strong><a href="user_profile.php?id=<?php echo intval($post['user_id']); ?>" style="color: var(--primary-green); text-decoration:none;"><?php echo htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?></a></strong>
                            <br>
                            <small style="color: #888;"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></small>
                            <?php if ($post['status'] === 'pending'): ?>
                                <span style="background: #ffeeba; color: #856404; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px; border: 1px solid #ffeeba;">
                                    <i class="fas fa-clock"></i> En attente
                                </span>
                            <?php endif; ?>
                            <?php if ($post['domain_name']): ?>
                                <span style="background: var(--accent-yellow); color: #333; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px;">
                                    <?php echo htmlspecialchars($post['domain_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div style="display:inline-flex; gap:5px;">
                            <?php if ($post['status'] === 'pending'): ?>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="approve_post" style="color: green; font-size: 1.2rem; background:none; border:none; cursor:pointer;" title="Approuver">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce post ?');">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="delete_post" style="color: red; font-size: 1.2rem; background:none; border:none; cursor:pointer;" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="post-content">
                    <?php if (!empty($post['title'])): ?>
                        <h3 style="margin-bottom: 0.5rem; color: var(--primary-green);"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <?php endif; ?>

                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    
                    <?php if ($post['image']): // 'image' contains the path to media ?>
                        <?php if ($post['media_type'] === 'video'): ?>
                            <video controls style="width: 100%; border-radius: 8px; margin-top: 15px;">
                                <source src="<?php echo htmlspecialchars($post['image']); ?>" type="video/mp4">
                                Votre navigateur ne supporte pas la vidéo.
                            </video>
                        <?php elseif ($post['media_type'] === 'audio'): ?>
                            <audio controls style="width: 100%; margin-top: 15px;">
                                <source src="<?php echo htmlspecialchars($post['image']); ?>" type="audio/mpeg">
                                Votre navigateur ne supporte pas l'audio.
                            </audio>
                        <?php elseif ($post['media_type'] === 'document'): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-file-pdf" style="font-size: 2rem; color: #dc3545;"></i>
                                <div>
                                    <strong>Document / Livre</strong>
                                    <br>
                                    <a href="<?php echo htmlspecialchars($post['image']); ?>" download class="btn-submit" style="display: inline-block; padding: 5px 10px; margin-top: 5px; font-size: 0.9rem; width: auto;">
                                        <i class="fas fa-download"></i> Télécharger
                                    </a>
                                </div>
                            </div>
                        <?php else: // Image or default ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="responsive-img" style="border-radius: 8px; margin-top: 15px;">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="action-buttons">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" name="toggle_like" class="action-btn" style="<?php echo $post['user_liked'] ? 'color: var(--brand); background: #e0f2f1; border-color: var(--brand);' : ''; ?>">
                            <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-thumbs-up"></i> <?php echo $post['like_count']; ?> J'aime
                        </button>
                    </form>
                    
                    <a href="post.php?id=<?php echo $post['id']; ?>" class="action-btn">
                        <i class="far fa-comment"></i> <?php echo $post['comment_count']; ?> Commenter
                    </a>

                    <?php
                        $canMessage = false;
                        if ($post['user_id'] != $userId) {
                            $stmt2 = $pdo->prepare("SELECT 1 FROM friendships WHERE status='accepted' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) LIMIT 1");
                            $stmt2->execute([$userId, $post['user_id'], $post['user_id'], $userId]);
                            $canMessage = (bool)$stmt2->fetchColumn();
                        }
                        if ($canMessage):
                    ?>
                        <a href="messages.php?friend_id=<?php echo intval($post['user_id']); ?>" class="action-btn">
                            <i class="far fa-paper-plane"></i> Message
                        </a>
                    <?php endif; ?>

                    <?php
                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                        $postUrl = $baseUrl . '/post.php?id=' . intval($post['id']);
                        $text = !empty($post['title']) ? $post['title'] : 'Découvrez cette publication sur KAYOKA';
                        $encodedUrl = rawurlencode($postUrl);
                        $encodedText = rawurlencode($text);
                    ?>
                    
                    <div class="share-container">
                        <button class="action-btn" style="width:100%" onclick="this.nextElementSibling.classList.toggle('show'); event.stopPropagation(); return false;">
                            <i class="fas fa-share-square"></i> Partager
                        </button>
                        <div class="share-menu">
                            <a href="javascript:void(0)" class="share-item" onclick="copyShareLink('<?php echo htmlspecialchars($postUrl); ?>')">
                                <i class="fas fa-link"></i> Copier le lien
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo $encodedText; ?>%20<?php echo $encodedUrl; ?>" class="share-item" target="_blank" rel="noopener">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encodedUrl; ?>" class="share-item" target="_blank" rel="noopener">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?php echo $encodedText; ?>&url=<?php echo $encodedUrl; ?>" class="share-item" target="_blank" rel="noopener">
                                <i class="fab fa-x-twitter"></i> Twitter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="display:flex; justify-content:center; gap:10px; margin-top:10px;">
                <?php
                    $base = 'social.php?'.http_build_query(array_merge($_GET, ['page'=>max(1, $page-1)]));
                    $next = 'social.php?'.http_build_query(array_merge($_GET, ['page'=>$page+1]));
                ?>
                <a href="<?php echo $base; ?>" class="btn-submit" style="width:auto; <?php echo $page<=1?'opacity:0.5; pointer-events:none;':''; ?>">Précédent</a>
                <a href="<?php echo $next; ?>" class="btn-submit" style="width:auto;">Suivant</a>
            </div>
        </div>

        <!-- COLONNE DROITE : À LA UNE & AMIS -->
        <div class="sidebar-column">
            
            <!-- DEMANDES D'AMIS (Si existantes) -->
            <?php if (count($friendRequests) > 0): ?>
            <div class="card" style="border-left: 4px solid var(--accent-yellow);">
                <h4 style="margin-bottom: 1rem;"><i class="fas fa-user-clock"></i> Invitations reçues</h4>
                <?php foreach ($friendRequests as $req): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center;">
                        <img src="<?php echo $req['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/30' : 'uploads/profiles/'.$req['profile_pic']; ?>" class="profile-pic-small">
                        <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($req['prenom']); ?></span>
                    </div>
                    <div>
                        <a href="?accept_friend=<?php echo $req['id']; ?>" style="color: green; margin-right: 5px;"><i class="fas fa-check"></i></a>
                        <a href="?reject_friend=<?php echo $req['id']; ?>" style="color: red;" onclick="return confirm('Refuser cette invitation ?');"><i class="fas fa-times"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- INVITATIONS ENVOYÉES -->
            <?php if (count($sentRequests) > 0): ?>
            <div class="card" style="border-left: 4px solid var(--accent-blue);">
                <h4 style="margin-bottom: 1rem;"><i class="fas fa-paper-plane"></i> Invitations envoyées</h4>
                <?php foreach ($sentRequests as $req): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center;">
                        <img src="<?php echo $req['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/30' : 'uploads/profiles/'.$req['profile_pic']; ?>" class="profile-pic-small">
                        <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($req['prenom']); ?></span>
                    </div>
                    <div>
                        <form action="cancel_invite.php" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $req['id']; ?>">
                            <input type="hidden" name="redirect" value="social.php">
                            <button type="submit" style="color: red; background:none; border:none; cursor:pointer;" title="Annuler l'invitation"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- À LA UNE (NEWS) -->
            <h2 class="section-title"><i class="fas fa-newspaper"></i> À la Une</h2>
            <?php foreach ($newsList as $news): ?>
            <div class="card news-card">
                <span class="news-date"><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($news['created_at'])); ?></span>
                <h4><?php echo htmlspecialchars($news['title']); ?></h4>
                <p style="font-size: 0.9rem; margin-top: 5px;">
                    <?php echo substr(htmlspecialchars($news['content']), 0, 80) . '...'; ?>
                </p>
                <?php if ($news['image']): ?>
                    <img src="<?php echo htmlspecialchars($news['image']); ?>" class="responsive-img" style="object-fit: cover; border-radius: 5px; margin-top: 10px;">
                <?php endif; ?>
                <a href="actualites.php" style="display: block; margin-top: 10px; font-size: 0.85rem; color: var(--primary-green);">Lire la suite &rarr;</a>
            </div>
            <?php endforeach; ?>

            <!-- SUGGESTIONS D'AMIS -->
            <h2 class="section-title" style="margin-top: 3rem;"><i class="fas fa-user-plus"></i> Suggestions</h2>
            <?php foreach ($suggestions as $user): ?>
            <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 10px;">
                <div style="display: flex; align-items: center;">
                    <img src="<?php echo $user['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/30' : 'uploads/profiles/'.$user['profile_pic']; ?>" class="profile-pic-small">
                    <span style="font-size: 0.9rem; font-weight: bold;"><?php echo htmlspecialchars($user['prenom']); ?></span>
                </div>
                <a href="?add_friend=<?php echo $user['id']; ?>" class="btn-small" style="background: var(--accent-blue); color: white; padding: 3px 8px; border-radius: 4px; text-decoration: none; font-size: 0.8rem;">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

    <script>
        function copyShareLink(url) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function(){ alert('Lien copié dans le presse-papiers'); });
            } else {
                const temp = document.createElement('textarea');
                temp.value = url;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                alert('Lien copié dans le presse-papiers');
            }
        }
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
