<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Marquer tout comme lu
if (isset($_POST['mark_all_read'])) {
    if (csrf_verify($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    header('Location: notifications.php');
    exit;
}

// Récupérer les notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.nom, u.prenom, u.profile_pic, p.title as post_title
    FROM notifications n
    JOIN users u ON n.actor_id = u.id
    JOIN posts p ON n.post_id = p.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Marquer les notifications affichées comme lues (optionnel, ou via clic)
// Pour l'instant on laisse l'utilisateur cliquer ou on marque tout comme lu via bouton
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notif-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: #fff;
            transition: background 0.2s;
        }
        .notif-item.unread {
            background: #f0fdf4; /* Light green for unread */
        }
        .notif-item:hover {
            background: #f9fafb;
        }
        .notif-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.2rem;
        }
        .icon-like { background: #fee2e2; color: #ef4444; }
        .icon-comment { background: #dbeafe; color: #3b82f6; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="padding: 2rem 0; max-width: 800px;">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid #eee;">
                <h2 class="section-title" style="margin:0;">Notifications</h2>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <button type="submit" name="mark_all_read" class="btn-outline" style="font-size:0.9rem;">
                        <i class="fas fa-check-double"></i> Tout marquer comme lu
                    </button>
                </form>
            </div>

            <?php if (empty($notifications)): ?>
                <div style="text-align:center; padding: 2rem; color: #888;">
                    <i class="far fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity:0.5;"></i>
                    <p>Aucune notification pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="stack">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                            <a href="user_profile.php?id=<?php echo $notif['actor_id']; ?>">
                                <img src="<?php echo $notif['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/40' : 'uploads/profiles/'.$notif['profile_pic']; ?>" class="profile-pic-small" alt="User">
                            </a>
                            
                            <div style="flex:1;">
                                <div>
                                    <strong><a href="user_profile.php?id=<?php echo $notif['actor_id']; ?>" style="text-decoration:none; color:inherit;"><?php echo htmlspecialchars($notif['prenom'] . ' ' . $notif['nom']); ?></a></strong>
                                    <?php if ($notif['type'] === 'like'): ?>
                                        a aimé votre publication
                                    <?php else: ?>
                                        a commenté votre publication
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.9rem; color: #666; margin-top: 4px;">
                                    <a href="post.php?id=<?php echo $notif['post_id']; ?>" style="color: inherit; text-decoration: none; font-weight: 500;">
                                        "<?php echo htmlspecialchars(mb_strimwidth($notif['post_title'] ?: 'Publication sans titre', 0, 50, '...')); ?>"
                                    </a>
                                </div>
                                <small style="color: #999;"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                            </div>

                            <div class="notif-icon <?php echo $notif['type'] === 'like' ? 'icon-like' : 'icon-comment'; ?>">
                                <i class="fas <?php echo $notif['type'] === 'like' ? 'fa-heart' : 'fa-comment'; ?>"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
