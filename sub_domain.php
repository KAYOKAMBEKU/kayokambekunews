<?php
session_start();
require_once 'db.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT sd.*, d.name as dname, d.icon FROM sub_domains sd JOIN domains d ON d.id=sd.domain_id WHERE sd.id=?");
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) { header('Location: domaines.php'); exit; }
$stmt = $pdo->prepare("SELECT p.*, u.nom, u.prenom FROM posts p JOIN users u ON u.id=p.user_id WHERE p.status='approved' AND (p.sub_domain_id=? OR p.domain_id=?) ORDER BY p.created_at DESC LIMIT 20");
$stmt->execute([$id, $sub['domain_id']]);
$posts = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT t.* FROM trainer_subdomains ts JOIN trainers t ON t.id=ts.trainer_id WHERE ts.sub_domain_id=?");
$stmt->execute([$id]);
$trainers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sub['name']); ?> - Sous-domaine</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container section">
    <div class="card">
        <div style="display:flex; align-items:center; gap:12px;">
            <i class="<?php echo $sub['icon']; ?>" style="font-size:2rem; color:var(--brand);"></i>
            <h2 class="section-title" style="margin:0;"><?php echo htmlspecialchars($sub['dname']); ?> / <?php echo htmlspecialchars($sub['name']); ?></h2>
        </div>
        <p class="text-muted" style="margin-top:8px;">Sous-domaine lié au domaine ci-dessus.</p>
        <?php if (!empty($trainers)): ?>
        <div style="margin-top:8px;">
            <strong>Formateurs associés:</strong>
            <div class="stack" style="margin-top:6px;">
                <?php foreach ($trainers as $t): ?>
                    <span style="display:inline-block; background:#eef2f7; padding:6px 10px; border-radius:999px;"><?php echo htmlspecialchars($t['name'].' '.($t['title']?('— '.$t['title']):'')); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <h3 class="section-title">Publications</h3>
    <?php if (empty($posts)): ?>
        <div class="card-soft">Aucune publication pour ce sous-domaine.</div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($posts as $p): ?>
            <div class="card-soft">
                <strong><?php echo htmlspecialchars($p['prenom'].' '.$p['nom']); ?></strong>
                <small style="color:#888; display:block;"><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></small>
                <?php if (!empty($p['title'])): ?>
                    <h4 style="margin-top:6px;"><?php echo htmlspecialchars($p['title']); ?></h4>
                <?php endif; ?>
                <p><?php echo nl2br(htmlspecialchars(mb_strimwidth($p['content'],0,160,'…'))); ?></p>
                <a href="post.php?id=<?php echo intval($p['id']); ?>" class="btn-outline" style="width:auto; text-decoration:none;">Voir plus</a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
