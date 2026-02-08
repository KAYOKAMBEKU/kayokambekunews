<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

// Si pas connecté, redirection
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Traitement du formulaire de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die("Erreur de sécurité (CSRF).");
    }
    
    $content = trim($_POST['content'] ?? '');
    if (!empty($content) && $id > 0) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $id, $content]);
        
        // Notification
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId && $ownerId != $userId) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?, ?, 'comment', ?)");
            $stmt->execute([$ownerId, $userId, $id]);
        }
        
        // Redirection pour éviter la resoumission
        header("Location: post.php?id=$id#comment-form");
        exit;
    }
}

// Récupération du post
$stmt = $pdo->prepare("SELECT p.*, u.nom, u.prenom, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.status = 'approved' 
        LIMIT 1");
$stmt->execute([$userId, $id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $ogTitle = "Publication introuvable - KAYOKA";
    $ogDesc = "Cette publication n'existe pas ou n'est pas approuvée.";
    $ogImage = "https://via.placeholder.com/600x315?text=KAYOKA";
} else {
    $ogTitle = !empty($post['title']) ? $post['title'] : "Publication de " . $post['prenom'] . " " . $post['nom'];
    $excerpt = trim(strip_tags($post['content']));
    if (strlen($excerpt) > 150) { $excerpt = substr($excerpt, 0, 147) . '...'; }
    $ogDesc = $excerpt ?: "Publication sur KAYOKA.";
    $ogImage = (!empty($post['image']) && $post['media_type'] === 'image') ? (strpos($post['image'], 'http') === 0 ? $post['image'] : $post['image']) : "https://via.placeholder.com/600x315?text=KAYOKA";
}

// Récupération des commentaires
$comments = [];
if ($post) {
    $stmt = $pdo->prepare("SELECT c.*, u.nom, u.prenom, u.profile_pic 
                           FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.post_id = ? 
                           ORDER BY c.created_at ASC");
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ogTitle); ?></title>
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDesc); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .comment-section {
            margin-top: 2rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }
        .comment-item {
            display: flex;
            gap: 15px;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }
        .comment-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            border-top-left-radius: 2px;
            flex: 1;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .comment-form {
            display: flex;
            gap: 15px;
            margin-top: 2rem;
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .comment-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
            height: 50px;
            transition: height 0.2s;
        }
        .comment-input:focus {
            height: 100px;
            outline: none;
            border-color: var(--primary-green);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="padding:2rem 0; max-width: 800px;">
        <a href="social.php" style="display:inline-block; margin-bottom: 1rem; color: #666; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Retour au fil
        </a>

        <?php if (!$post): ?>
            <div class="card">
                <h2>Publication introuvable</h2>
                <p>Cette page n'existe pas ou n'est plus disponible.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="post-header" style="justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <img src="<?php echo $post['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/40' : 'uploads/profiles/'.$post['profile_pic']; ?>" alt="User" class="profile-pic-small">
                        <div>
                            <strong><a href="user_profile.php?id=<?php echo intval($post['user_id']); ?>" style="color: var(--primary-green); text-decoration:none;"><?php echo htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?></a></strong>
                            <br>
                            <small style="color: #888;"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></small>
                        </div>
                    </div>
                </div>

                <?php if (!empty($post['title'])): ?>
                    <h2 style="color:var(--primary-green); margin-bottom: 1rem;"><?php echo htmlspecialchars($post['title']); ?></h2>
                <?php endif; ?>
                
                <div style="font-size: 1.1rem; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>

                <?php if (!empty($post['image'])): ?>
                    <?php if ($post['media_type'] === 'video'): ?>
                        <video controls style="width:100%;border-radius:8px;margin-top:15px;">
                            <source src="<?php echo htmlspecialchars($post['image']); ?>" type="video/mp4">
                        </video>
                    <?php elseif ($post['media_type'] === 'audio'): ?>
                        <audio controls style="width:100%;margin-top:15px;">
                            <source src="<?php echo htmlspecialchars($post['image']); ?>" type="audio/mpeg">
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
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" class="responsive-img" style="border-radius:8px;margin-top:15px;">
                    <?php endif; ?>
                <?php endif; ?>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; color: #666;">
                    <i class="far fa-thumbs-up"></i> <?php echo $post['like_count']; ?> J'aime &nbsp;&nbsp;
                    <i class="far fa-comment"></i> <?php echo count($comments); ?> Commentaires
                </div>

                <!-- Section Commentaires -->
                <div class="comment-section">
                    <h3>Commentaires</h3>
                    
                    <?php if (empty($comments)): ?>
                        <p style="color: #999; font-style: italic;">Soyez le premier à commenter !</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <a href="user_profile.php?id=<?php echo $comment['user_id']; ?>">
                                    <img src="<?php echo $comment['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/40' : 'uploads/profiles/'.$comment['profile_pic']; ?>" alt="User" class="profile-pic-small" style="width: 40px; height: 40px;">
                                </a>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <strong><a href="user_profile.php?id=<?php echo $comment['user_id']; ?>" style="text-decoration:none; color:inherit;"><?php echo htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']); ?></a></strong>
                                        <small style="color: #888;"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                    <div><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Formulaire d'ajout -->
                    <form id="comment-form" class="comment-form" action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <img src="<?php echo 'uploads/profiles/' . ($_SESSION['user_profile_pic'] ?? 'default.png'); ?>" alt="Me" class="profile-pic-small" style="width: 40px; height: 40px;">
                        <div style="flex:1;">
                            <textarea name="content" class="comment-input" placeholder="Écrivez un commentaire..." required></textarea>
                            <div style="text-align: right; margin-top: 10px;">
                                <button type="submit" name="submit_comment" class="btn-submit" style="width: auto; padding: 8px 20px;">Publier</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>