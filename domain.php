<?php
session_start();
require_once 'db.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
$stmt->execute([$id]);
$domain = $stmt->fetch();
if (!$domain) { header('Location: domaines.php'); exit; }
$stmt = $pdo->prepare("SELECT * FROM sub_domains WHERE domain_id = ? ORDER BY name");
$stmt->execute([$id]);
$subs = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT p.*, u.nom, u.prenom FROM posts p JOIN users u ON p.user_id=u.id WHERE p.status='approved' AND p.domain_id = ? ORDER BY p.created_at DESC LIMIT 20");
$stmt->execute([$id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($domain['name']); ?> - Domaine</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container section">
    <div class="card">
        <div style="display:flex; align-items:center; gap:12px;">
            <i class="<?php echo $domain['icon']; ?>" style="font-size:2rem; color:var(--brand);"></i>
            <h2 class="section-title" style="margin:0;"><?php echo htmlspecialchars($domain['name']); ?></h2>
        </div>
        <p class="text-muted" style="margin-top:8px;"><?php echo nl2br(htmlspecialchars($domain['description'])); ?></p>
        <?php if (!empty($subs)): ?>
        <div style="margin-top:8px;">
            <strong>Sous-domaines:</strong>
            <div class="stack" style="margin-top:6px;">
                <?php foreach ($subs as $sd): ?>
                    <span style="display:inline-block; background:#eef2f7; padding:6px 10px; border-radius:999px;"><?php echo htmlspecialchars($sd['name']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <h3 class="section-title">Publications de ce domaine</h3>
    <?php if (empty($posts)): ?>
        <div class="card-soft">Aucune publication pour l’instant.</div>
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
