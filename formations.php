<?php
session_start();
require_once 'db.php';
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$rows = $pdo->query("SELECT f.*, sd.name as sname, d.name as dname 
    FROM formations f 
    JOIN sub_domains sd ON sd.id=f.sub_domain_id 
    JOIN domains d ON d.id=sd.domain_id 
    WHERE f.status='published'
    ORDER BY f.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formations</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="section-title">Formations</h2>
        <div>
            <?php if($uid): ?>
                <a href="my_formations.php" class="btn-outline" style="text-decoration:none; margin-right:10px;"><i class="fas fa-user-graduate"></i> Mes Formations</a>
            <?php endif; ?>
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role']==='admin'): ?>
                <a class="btn-outline" href="add_formation.php" style="text-decoration:none;">Ajouter</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($rows)): ?>
        <div class="card-soft">Aucune formation disponible.</div>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($rows as $f): ?>
        <div class="card-soft">
            <div class="text-muted"><?php echo htmlspecialchars($f['dname'].' / '.$f['sname']); ?></div>
            <h3><?php echo htmlspecialchars($f['title']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars(mb_strimwidth($f['description'],0,180,'…'))); ?></p>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div><strong><?php echo number_format($f['price'],2); ?> USD</strong></div>
                <div>
                    <a class="btn-outline" style="text-decoration:none;" href="view_formation.php?id=<?php echo $f['id']; ?>">Voir</a>
                    <?php if($uid): ?><a class="btn-submit" style="text-decoration:none; width:auto;" href="enroll_formation.php?id=<?php echo $f['id']; ?>">S'inscrire</a><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
