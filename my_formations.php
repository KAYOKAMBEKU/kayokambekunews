<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];

// Récupérer les formations auxquelles l'utilisateur est inscrit (active ou payée)
$query = "
    SELECT f.*, sd.name as sname, d.name as dname, fe.status as enrollment_status, fe.created_at as enrollment_date
    FROM formations f
    JOIN sub_domains sd ON sd.id = f.sub_domain_id
    JOIN domains d ON d.id = sd.domain_id
    JOIN formation_enrollments fe ON fe.formation_id = f.id
    WHERE fe.user_id = ? AND fe.status = 'active'
    ORDER BY fe.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$uid]);
$my_formations = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Formations</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container-narrow section">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2 class="section-title">Mes Formations</h2>
        <a href="formations.php" class="btn-outline" style="text-decoration:none;">Parcourir le catalogue</a>
    </div>

    <?php if (empty($my_formations)): ?>
        <div class="card-soft" style="text-align:center; padding:40px;">
            <i class="fas fa-graduation-cap" style="font-size:3rem; color:#ccc; margin-bottom:20px;"></i>
            <h3>Vous n'êtes inscrit à aucune formation.</h3>
            <p class="text-muted">Découvrez nos cours et commencez à apprendre dès aujourd'hui.</p>
            <a href="formations.php" class="btn-submit" style="width:auto; display:inline-block; margin-top:10px;">Voir les formations</a>
        </div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($my_formations as $f): ?>
            <div class="card-soft">
                <div class="text-muted"><?php echo htmlspecialchars($f['dname'].' / '.$f['sname']); ?></div>
                <h3><?php echo htmlspecialchars($f['title']); ?></h3>
                
                <div style="margin: 10px 0;">
                    <span class="badge badge-success">Inscrit le <?php echo date('d/m/Y', strtotime($f['enrollment_date'])); ?></span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
                    <a class="btn-submit" style="text-decoration:none; width:100%; text-align:center;" href="formation_view.php?id=<?php echo $f['id']; ?>">
                        Accéder au cours <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
