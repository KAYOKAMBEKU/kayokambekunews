<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current = basename($_SERVER['PHP_SELF']);

// Count unread notifications
$unreadNotifs = 0;
if (isset($_SESSION['user_id'])) {
    if (!isset($pdo)) { require_once 'db.php'; }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadNotifs = $stmt->fetchColumn();
    } catch (Exception $e) { /* Ignore if table doesn't exist yet */ }
}
?>

<header>
    <div class="container header-content">
        <div class="logo-container">
            <img src="img/logo.jpg" alt="KAYOKA Logo" class="logo-img">
            <div class="logo">
                <h1><a href="index.php" class="wordmark">KAYOKA MBEKU NEWS</a></h1>
            </div>
        </div>
        <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false"><i class="fas fa-bars"></i></button>
        <nav id="main-nav">
            <ul>
                <li class="mobile-menu-header">
                    <img src="img/logo.jpg" alt="KAYOKA Logo" class="mobile-logo-img">
                    <span class="mobile-brand">KAYOKA</span>
                </li>
                <li><a href="index.php" class="<?php echo $current === 'index.php' ? 'active' : ''; ?>">Accueil</a></li>
                <li><a href="actualites.php" class="<?php echo $current === 'actualites.php' ? 'active' : ''; ?>">Actualités</a></li>
                <li><a href="domaines.php" class="<?php echo $current === 'domaines.php' ? 'active' : ''; ?>">Domaines</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li>
                        <a href="notifications.php" class="<?php echo $current === 'notifications.php' ? 'active' : ''; ?>" style="position: relative;">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotifs > 0): ?>
                                <span style="position: absolute; top: -5px; right: -10px; background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 50%;"><?php echo $unreadNotifs; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="dashboard.php" class="btn-nav <?php echo $current === 'dashboard.php' ? 'active' : ''; ?>">Mon Compte</a></li>
                    <li><a href="logout.php" class="btn-nav" style="width:auto; border-color: #ef4444; color: #ef4444 !important;">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-submit" style="width:auto;">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
