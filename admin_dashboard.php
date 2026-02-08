<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 1. Approbation des Posts (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_post'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#approvals'); exit; }
    $postId = intval($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        $stmt = $pdo->prepare("UPDATE posts SET status = 'approved' WHERE id = ?");
        $stmt->execute([$postId]);
    }
    header('Location: admin_dashboard.php#approvals');
    exit;
}

// 2. Suppression des Posts (POST) + suppression du média
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#approvals'); exit; }
    $postId = intval($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $img = $stmt->fetchColumn();
        if ($img && strpos($img, 'uploads/posts/') === 0 && file_exists($img)) { @unlink($img); }
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
    }
    header('Location: admin_dashboard.php#approvals');
    exit;
}

// 3. Gestion des News (Actualités Officielles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $imagePath = null;
    $mentionUserId = isset($_POST['mention_user_id']) && $_POST['mention_user_id'] !== '' ? intval($_POST['mention_user_id']) : null;

    if (!empty($title) && !empty($content)) {
        // Upload Image News
        if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['news_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = 'news_' . uniqid() . '.' . $ext;
                if (!is_dir('uploads/news')) {
                    mkdir('uploads/news', 0777, true);
                }
                if (move_uploaded_file($_FILES['news_image']['tmp_name'], 'uploads/news/' . $newFilename)) {
                    $imagePath = 'uploads/news/' . $newFilename;
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO news (title, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $imagePath]);
        $newsId = $pdo->lastInsertId();
        if ($mentionUserId) {
            $stmt = $pdo->prepare("INSERT INTO news_mentions (news_id, user_id) VALUES (?, ?)");
            $stmt->execute([$newsId, $mentionUserId]);
        }
        $news_msg = "Actualité publiée avec succès.";
    }
    header('Location: admin_dashboard.php#news');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_news'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#news'); exit; }
    $newsId = intval($_POST['news_id'] ?? 0);
    if ($newsId > 0) {
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$newsId]);
    }
    header('Location: admin_dashboard.php#news');
    exit;
}

// 4. Gestion Utilisateurs (Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#users'); exit; }
    $userIdToDelete = intval($_POST['user_id'] ?? 0);
    // Empêcher de se supprimer soi-même
    if ($userIdToDelete != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userIdToDelete]);
    }
    header('Location: admin_dashboard.php#users');
    exit;
}

// 5. Gestion Domaines (Ajout)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_domain'])) {
    $domainName = trim($_POST['domain_name']);
    $domainIcon = trim($_POST['domain_icon']);
    $domainDesc = trim($_POST['domain_desc']);
    
    if (!empty($domainName)) {
        $stmt = $pdo->prepare("INSERT INTO domains (name, icon, description) VALUES (?, ?, ?)");
        $stmt->execute([$domainName, $domainIcon, $domainDesc]);
    }
    header('Location: admin_dashboard.php#domains');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_domain'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#domains'); exit; }
    $domainId = intval($_POST['domain_id'] ?? 0);
    if ($domainId > 0) {
        $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ?");
        $stmt->execute([$domainId]);
    }
    header('Location: admin_dashboard.php#domains');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subdomain'])) {
    $domainId = intval($_POST['sub_for_domain'] ?? 0);
    $name = trim($_POST['sub_name'] ?? '');
    if ($domainId > 0 && $name !== '') {
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM sub_domains WHERE domain_id = ? AND name = ?");
        $check->execute([$domainId, $name]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO sub_domains (domain_id, name) VALUES (?, ?)");
            $stmt->execute([$domainId, $name]);
        }
    }
    header('Location: admin_dashboard.php#domains'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subdomain'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#domains'); exit; }
    $subId = intval($_POST['sub_id'] ?? 0);
    if ($subId > 0) {
        $stmt = $pdo->prepare("DELETE FROM sub_domains WHERE id = ?");
        $stmt->execute([$subId]);
    }
    header('Location: admin_dashboard.php#domains'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $uid = intval($_POST['trainer_user_id'] ?? 0);
    $name = trim($_POST['trainer_name'] ?? '');
    $title = trim($_POST['trainer_title'] ?? '');
    if ($uid > 0) {
        $stmt = $pdo->prepare("SELECT prenom, nom FROM users WHERE id=?"); $stmt->execute([$uid]); $u=$stmt->fetch();
        if ($u) { $name = $name !== '' ? $name : ($u['prenom'].' '.$u['nom']); }
    }
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO trainers (user_id, name, title) VALUES (?, ?, ?)");
        $stmt->execute([$uid ?: null, $name, $title]);
    }
    header('Location: admin_dashboard.php#domains'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_trainer'])) {
    $trainerId = intval($_POST['trainer_id'] ?? 0);
    $subId = intval($_POST['assign_sub_id'] ?? 0);
    $t = trim($_POST['assign_title'] ?? '');
    if ($trainerId > 0 && $subId > 0) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO trainer_subdomains (trainer_id, sub_domain_id, title) VALUES (?, ?, ?)");
        $stmt->execute([$trainerId, $subId, $t]);
    }
    header('Location: admin_dashboard.php#domains'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_social_link'])) {
    $platform = $_POST['social_platform'] ?? '';
    $url = trim($_POST['social_url'] ?? '');
    if (in_array($platform, ['whatsapp','facebook','instagram']) && $url !== '') {
        $stmt = $pdo->prepare("INSERT INTO social_links (user_id, platform, url) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE url=VALUES(url)");
        $stmt->execute([$_SESSION['user_id'], $platform, $url]);
    }
    header('Location: admin_dashboard.php#news'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_status_to_news'])) {
    $plat = $_POST['status_platform'] ?? '';
    $title = trim($_POST['status_title'] ?? '');
    $content = trim($_POST['status_content'] ?? '');
    if ($title !== '' && $content !== '' && in_array($plat, ['whatsapp','facebook','instagram'])) {
        $full = "[Statut ".$plat."] ".$content;
        $stmt = $pdo->prepare("INSERT INTO news (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $full]);
        $news_msg = "Statut ".$plat." publié en actualité.";
    }
    header('Location: admin_dashboard.php#news');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_formation'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: admin_dashboard.php#formations'); exit; }
    $fid = intval($_POST['formation_id'] ?? 0);
    if ($fid > 0) {
        $stmt = $pdo->prepare("DELETE FROM formations WHERE id = ?");
        $stmt->execute([$fid]);
    }
    header('Location: admin_dashboard.php#formations'); exit;
}

// Récupérer les posts en attente
$stmt = $pdo->query("SELECT p.*, u.nom, u.prenom FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at DESC");
$pendingPosts = $stmt->fetchAll();

// Récupérer les News
$stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
$allNews = $stmt->fetchAll();

// Récupérer les Utilisateurs
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$allUsers = $stmt->fetchAll();

// Récupérer les Domaines
$stmt = $pdo->query("SELECT * FROM domains");
$allDomains = $stmt->fetchAll();

// Récupérer les Formations
$stmt = $pdo->query("SELECT f.*, sd.name as sname, d.name as dname, (SELECT COUNT(*) FROM formation_enrollments fe WHERE fe.formation_id=f.id AND fe.status='active') as student_count FROM formations f JOIN sub_domains sd ON sd.id=f.sub_domain_id JOIN domains d ON d.id=sd.domain_id ORDER BY f.created_at DESC");
$allFormations = $stmt->fetchAll();

// Récupérer les avis (Feedback)
$stmt = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC");
$feedbacks = $stmt->fetchAll();

// Récupérer les visites (Stats)
$stmt = $pdo->query("SELECT COUNT(*) FROM visits");
$totalVisits = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - KAYOKA</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-bolt"></i> KAYOKA ADMIN
            </a>
        </div>
        <div class="sidebar-nav">
            <div class="nav-header">Principal</div>
            <a href="#dashboard" class="nav-item active" onclick="showSection('dashboard', this)">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
            <a href="#users" class="nav-item" onclick="showSection('users', this)">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
            <a href="#domains" class="nav-item" onclick="showSection('domains', this)">
                <i class="fas fa-layer-group"></i> Domaines
            </a>
            <a href="#formations" class="nav-item" onclick="showSection('formations', this)">
                <i class="fas fa-graduation-cap"></i> Formations
            </a>
            
            <div class="nav-header">Contenu</div>
            <a href="#news" class="nav-item" onclick="showSection('news', this)">
                <i class="fas fa-newspaper"></i> Actualités
            </a>
            <a href="#approvals" class="nav-item" onclick="showSection('approvals', this)">
                <i class="fas fa-check-circle"></i> Approbations
                <?php if(count($pendingPosts) > 0): ?>
                    <span class="badge badge-warning" style="margin-left:auto;"><?php echo count($pendingPosts); ?></span>
                <?php endif; ?>
            </a>
            <a href="#feedback" class="nav-item" onclick="showSection('feedback', this)">
                <i class="fas fa-comment-dots"></i> Avis
            </a>

            <div class="nav-header">Système</div>
            <a href="index.php" class="nav-item">
                <i class="fas fa-external-link-alt"></i> Voir le site
            </a>
            <a href="logout.php" class="nav-item" style="color: #f87171;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Content -->
    <main class="admin-content">
        <header class="admin-header">
            <button class="header-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div style="flex:1;"></div>
            <div class="header-user">
                <span>Admin</span>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </header>

        <div class="main-container">
            
            <!-- SECTION DASHBOARD -->
            <div id="section-dashboard" class="admin-section">
                <div class="page-header">
                    <h2 class="page-title">Tableau de bord</h2>
                    <span class="text-muted"><?php echo date('d F Y'); ?></span>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo $totalVisits; ?></div>
                                <div class="stat-label">Visites totales</div>
                            </div>
                            <div class="stat-icon blue"><i class="fas fa-eye"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo count($allUsers); ?></div>
                                <div class="stat-label">Utilisateurs inscrits</div>
                            </div>
                            <div class="stat-icon green"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo count($allNews); ?></div>
                                <div class="stat-label">Actualités publiées</div>
                            </div>
                            <div class="stat-icon purple"><i class="fas fa-newspaper"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo count($pendingPosts); ?></div>
                                <div class="stat-label">En attente</div>
                            </div>
                            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Statistiques des visites (Temps réel)</h3>
                    </div>
                    <div class="card-body">
                         <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));gap:15px;margin-bottom:20px;">
                            <div style="text-align:center; padding:15px; background:#f9fafb; border-radius:8px;">
                                <div style="font-size:0.85rem;color:#6b7280;margin-bottom:5px;">Aujourd'hui</div>
                                <div id="stat-today" style="font-size:1.5rem;font-weight:bold;color:#111827;">—</div>
                            </div>
                            <div style="text-align:center; padding:15px; background:#f9fafb; border-radius:8px;">
                                <div style="font-size:0.85rem;color:#6b7280;margin-bottom:5px;">En ligne (5 min)</div>
                                <div id="stat-online" style="font-size:1.5rem;font-weight:bold;color:#059669;">—</div>
                            </div>
                        </div>
                        <canvas id="visitsChart" height="80"></canvas>
                        <div style="margin-top:20px; text-align:right;">
                            <button id="stat-refresh" onclick="updateStats()" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i> Actualiser</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION UTILISATEURS -->
            <div id="section-users" class="admin-section" style="display:none;">
                <div class="page-header">
                    <h2 class="page-title">Gestion des Utilisateurs</h2>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Liste des utilisateurs (<?php echo count($allUsers); ?>)</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Inscrit le</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($allUsers as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Bannir cet utilisateur ?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i> Bannir</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION DOMAINES -->
            <div id="section-domains" class="admin-section" style="display:none;">
                <div class="page-header">
                    <h2 class="page-title">Gestion des Domaines</h2>
                </div>
                
                <div class="grid-2">
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Ajouter un domaine</h3>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="form-group">
                                        <label>Nom du domaine</label>
                                        <input type="text" name="domain_name" class="form-control" placeholder="Ex: Technologie" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Icône (FontAwesome)</label>
                                        <input type="text" name="domain_icon" class="form-control" placeholder="Ex: fas fa-laptop">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <input type="text" name="domain_desc" class="form-control" placeholder="Description courte">
                                    </div>
                                    <button type="submit" name="add_domain" class="btn btn-primary">Ajouter</button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Liste des Domaines</h3>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="admin-table">
                                    <thead><tr><th>Nom</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($allDomains as $d): ?>
                                        <tr>
                                            <td><i class="<?php echo $d['icon']; ?>"></i> <?php echo htmlspecialchars($d['name']); ?></td>
                                            <td>
                                                <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce domaine ?')">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="domain_id" value="<?php echo $d['id']; ?>">
                                                    <button type="submit" name="delete_domain" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div>
                         <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Sous-domaines</h3>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" class="form-group" style="display:flex; gap:10px; margin-bottom:20px;">
                                    <select name="sub_for_domain" class="form-control" style="width:40%;">
                                        <?php foreach ($allDomains as $d): ?>
                                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="sub_name" class="form-control" placeholder="Nom du sous-domaine" required>
                                    <button type="submit" name="add_subdomain" class="btn btn-primary">Ajouter</button>
                                </form>
                                
                                <?php
                                    $domainMap = [];
                                    foreach ($allDomains as $d) $domainMap[$d['id']] = $d['name'];
                                    $subs = $pdo->query("SELECT sd.id, sd.name, sd.domain_id FROM sub_domains sd ORDER BY sd.domain_id, sd.name")->fetchAll();
                                ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <table class="admin-table">
                                        <thead><tr><th>Domaine Parent</th><th>Sous-domaine</th><th>Action</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($subs as $sd): ?>
                                            <tr>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($domainMap[$sd['domain_id']] ?? ''); ?></small></td>
                                                <td><?php echo htmlspecialchars($sd['name']); ?></td>
                                                <td>
                                                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce sous-domaine ?')">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <input type="hidden" name="sub_id" value="<?php echo $sd['id']; ?>">
                                                        <button type="submit" name="delete_subdomain" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION FORMATIONS -->
            <div id="section-formations" class="admin-section" style="display:none;">
                <div class="page-header">
                    <h2 class="page-title">Gestion des Formations</h2>
                    <a href="add_formation.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle Formation</a>
                </div>
                
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>Titre</th><th>Catégorie</th><th>Prix</th><th>Inscrits</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($allFormations as $f): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($f['title']); ?></div>
                                        <small class="text-muted"><?php echo $f['status'] === 'published' ? 'Publié' : 'Brouillon'; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($f['dname'] . ' / ' . $f['sname']); ?></td>
                                    <td><?php echo $f['price'] > 0 ? number_format($f['price'], 2) . ' USD' : 'Gratuit'; ?></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo $f['student_count']; ?></span>
                                    </td>
                                    <td>
                                        <a href="view_formation.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-eye"></i></a>
                                        <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette formation ? Tout le contenu associé sera perdu.')">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="formation_id" value="<?php echo $f['id']; ?>">
                                            <button type="submit" name="delete_formation" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION NEWS -->
            <div id="section-news" class="admin-section" style="display:none;">
                <div class="page-header">
                    <h2 class="page-title">Actualités Officielles</h2>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Publier une nouvelle actualité</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($news_msg)) echo "<div class='badge badge-success' style='margin-bottom:1rem; display:block;'>$news_msg</div>"; ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Titre</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Image (Optionnel)</label>
                                    <input type="file" name="news_image" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Contenu</label>
                                <textarea name="content" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Mentionner un utilisateur (Optionnel)</label>
                                <select name="mention_user_id" class="form-control">
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($allUsers as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['prenom'].' '.$u['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_news" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publier</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Convertir un statut en actualité</h3>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Plateforme</label>
                                    <select name="status_platform" class="form-control" required>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="instagram">Instagram</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Titre</label>
                                    <input type="text" name="status_title" class="form-control" placeholder="Titre de l’actualité" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Contenu du statut</label>
                                <textarea name="status_content" class="form-control" rows="3" placeholder="Collez ici votre statut" required></textarea>
                            </div>
                            <button type="submit" name="publish_status_to_news" class="btn btn-primary"><i class="fas fa-exchange-alt"></i> Publier en Actualité</button>
                        </form>
                        <div class="text-muted" style="margin-top:10px; font-size:0.9rem;">
                            Les API ne permettent pas d’importer automatiquement les statuts. Utilisez ce formulaire pour les publier rapidement côté admin.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Historique des actualités</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>Titre</th><th>Date</th><th>Image</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($allNews as $news): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($news['title']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($news['created_at'])); ?></td>
                                    <td><?php echo $news['image'] ? '<i class="fas fa-image text-muted"></i>' : '-'; ?></td>
                                    <td>
                                        <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette news ?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                            <button type="submit" name="delete_news" class="btn btn-danger btn-sm">Supprimer</button>
                                        </form>
                                        <?php
                                            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                                            $shareText = $news['title'] . " - Voir sur KAYOKA: " . $baseUrl . "/actualites.php";
                                            $waHref = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
                                        ?>
                                        <a href="<?php echo $waHref; ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="margin-left:8px;">
                                            <i class="fab fa-whatsapp"></i> Partager
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION APPROBATIONS -->
            <div id="section-approvals" class="admin-section" style="display:none;">
                <div class="page-header">
                    <h2 class="page-title">Publications en attente</h2>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($pendingPosts)): ?>
                            <div style="text-align:center; padding:2rem; color:var(--text-muted);">
                                <i class="fas fa-check-circle" style="font-size:3rem; margin-bottom:1rem; color:#d1fae5;"></i>
                                <p>Aucune publication en attente d'approbation.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingPosts as $post): ?>
                                <div style="border:1px solid var(--border); border-radius:8px; padding:1.5rem; margin-bottom:1.5rem;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?></strong>
                                            <span class="text-muted"> a soumis une publication le <?php echo date('d/m/Y à H:i', strtotime($post['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div style="background:#f9fafb; padding:1rem; border-radius:6px; margin-bottom:1rem;">
                                        <?php if($post['title']): ?>
                                            <h4 style="margin-top:0;"><?php echo htmlspecialchars($post['title']); ?></h4>
                                        <?php endif; ?>
                                        <p style="white-space:pre-wrap;"><?php echo htmlspecialchars($post['content']); ?></p>
                                        <?php if ($post['image']): ?>
                                            <div style="margin-top:10px;">
                                                <a href="<?php echo htmlspecialchars($post['image']); ?>" target="_blank" class="btn btn-sm btn-primary">Voir le média joint</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display:flex; gap:10px;">
                                        <form action="" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" name="approve_post" class="btn btn-success"><i class="fas fa-check"></i> Approuver</button>
                                        </form>
                                        <form action="" method="POST" onsubmit="return confirm('Rejeter et supprimer ce post ?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" name="delete_post" class="btn btn-danger"><i class="fas fa-times"></i> Rejeter</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

             <!-- SECTION AVIS -->
             <div id="section-feedback" class="admin-section" style="display:none;">
                <div class="page-header">
                    <h2 class="page-title">Avis des Visiteurs</h2>
                </div>
                <div class="card">
                    <div class="card-body table-responsive">
                        <?php if (empty($feedbacks)): ?>
                            <p class="text-muted">Aucun avis reçu pour le moment.</p>
                        <?php else: ?>
                            <table class="admin-table">
                                <thead><tr><th>Nom</th><th>Message</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($feedbacks as $fb): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($fb['visitor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($fb['content']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($fb['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

function showSection(sectionId, element) {
    // Hide all sections
    document.querySelectorAll('.admin-section').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show target section
    const target = document.getElementById('section-' + sectionId);
    if (target) {
        target.style.display = 'block';
    }

    // Update nav active state
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.remove('active');
    });
    if (element) {
        element.classList.add('active');
    }
    
    // Update hash without scrolling
    if(history.pushState) {
        history.pushState(null, null, '#' + sectionId);
    } else {
        location.hash = '#' + sectionId;
    }

    // Close sidebar on mobile if open
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
    }
}

// Stats & Charts
let visitsChart = null;

async function updateStats() {
    try {
        const res = await fetch('visits_stats.php');
        const data = await res.json();
        if(data.success) {
            document.getElementById('stat-today').innerText = data.today;
            document.getElementById('stat-online').innerText = data.online;
        }
    } catch(e) { console.error(e); }
    
    updateChart();
}

async function updateChart() {
    try {
        const res = await fetch('visits_timeseries.php');
        const data = await res.json();
        if(data.success) {
            const ctx = document.getElementById('visitsChart').getContext('2d');
            
            // We use 'days' by default for the chart view
            const labels = data.days.labels;
            const values = data.days.data;

            if(visitsChart) {
                visitsChart.destroy();
            }

            visitsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Visites (30 derniers jours)',
                        data: values,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    } catch(e) { console.error(e); }
}

// Initial check for hash or default
document.addEventListener('DOMContentLoaded', () => {
    // Init router
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const link = document.querySelector(`a[href="#${hash}"]`);
        if (link) {
            showSection(hash, link);
        } else {
            // Default fallback
            const defaultLink = document.querySelector(`a[href="#dashboard"]`);
            if (defaultLink) showSection('dashboard', defaultLink);
        }
    } else {
         const defaultLink = document.querySelector(`a[href="#dashboard"]`);
         if (defaultLink) showSection('dashboard', defaultLink);
    }
    
    // Init stats
    updateStats();
    // Auto refresh stats every 30s
    setInterval(updateStats, 30000);
});
</script>

</body>
</html>
