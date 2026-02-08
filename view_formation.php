<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT f.*, sd.name as sname, d.name as dname FROM formations f JOIN sub_domains sd ON sd.id=f.sub_domain_id JOIN domains d ON d.id=sd.domain_id WHERE f.id=?");
$stmt->execute([$id]);
$f = $stmt->fetch();
if (!$f) { header('Location: formations.php'); exit; }
$enrolled = false;
if ($uid) {
    $st = $pdo->prepare("SELECT 1 FROM payments WHERE user_id=? AND formation_id=? AND status='paid' LIMIT 1");
    $st->execute([$uid, $id]);
    $enrolled = $st->fetchColumn() ? true : false;
    if (!$enrolled) {
        $st = $pdo->prepare("SELECT 1 FROM formation_enrollments WHERE user_id=? AND formation_id=? AND status='active' LIMIT 1");
        $st->execute([$uid, $id]);
        $enrolled = $st->fetchColumn() ? true : false;
    }
}
$sessions = $pdo->prepare("SELECT s.*, t.name as tname FROM formation_sessions s JOIN trainers t ON t.id=s.trainer_id WHERE s.formation_id=? ORDER BY s.start_time DESC");
$sessions->execute([$id]);
$sessions = $sessions->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($f['title']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card-soft">
        <div class="text-muted"><?php echo htmlspecialchars($f['dname'].' / '.$f['sname']); ?></div>
        <h2 class="section-title"><?php echo htmlspecialchars($f['title']); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($f['description'])); ?></p>
<?php if ($f['resource_url']): ?>
            <p><a class="btn-outline" href="<?php echo htmlspecialchars($f['resource_url']); ?>" target="_blank" style="text-decoration:none;">Ressource</a></p>
        <?php endif; ?>
        <?php if (!$uid): ?>
            <p>Connectez-vous pour vous inscrire et suivre la formation.</p>
            <a href="login.php?redirect=enroll_formation.php?id=<?php echo $f['id']; ?>" class="btn-submit" style="width:auto; text-decoration:none;">Se connecter pour s'inscrire</a>
        <?php else: ?>
            <p>Statut: <?php echo $enrolled ? 'Inscrit' : 'Non inscrit'; ?>. Prix: <strong><?php echo number_format($f['price'],2); ?> USD</strong></p>
            <p class="text-muted">Période: du <?php echo date('d/m/Y H:i', strtotime($f['start_time'])); ?> au <?php echo $f['end_time'] ? date('d/m/Y H:i', strtotime($f['end_time'])) : '—'; ?></p>
            <?php if ($enrolled): ?>
                <a class="btn-submit" style="width:auto; text-decoration:none;" href="formation_view.php?id=<?php echo $f['id']; ?>">Accéder à la formation</a>
            <?php elseif (!$enrolled): ?>
                <?php if ($f['status']==='published' && strtotime($f['start_time']) <= time() && (!$f['end_time'] || strtotime($f['end_time']) >= time())): ?>
                    <a class="btn-submit" style="width:auto; text-decoration:none;" href="enroll_formation.php?id=<?php echo $f['id']; ?>">S'inscrire</a>
                <?php else: ?>
                    <span class="text-muted">Inscription indisponible (hors période ou non publiée).</span>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="card-soft">
        <h3>Sessions en ligne</h3>
        <?php if (empty($sessions)): ?>
            <p class="text-muted">Aucune session.</p>
        <?php else: ?>
            <div class="stack">
                <?php foreach ($sessions as $s): ?>
                    <div>
                        <strong><?php echo htmlspecialchars($s['tname']); ?></strong> — <?php echo date('d/m/Y H:i', strtotime($s['start_time'])); ?>
                        <?php if ($enrolled): ?>
                            <a class="btn-outline" style="text-decoration:none; margin-left:8px;" href="<?php echo htmlspecialchars($s['link']); ?>" target="_blank">Rejoindre</a>
                        <?php else: ?>
                            <span class="text-muted" style="margin-left:8px;">Réservé aux inscrits</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php
            $isTrainer = false;
            if ($uid) {
                $q = $pdo->prepare("SELECT ts.trainer_id FROM trainer_subdomains ts JOIN trainers t ON t.id=ts.trainer_id WHERE t.user_id=? AND ts.sub_domain_id=? LIMIT 1");
                $q->execute([$uid, $f['sub_domain_id']]);
                $tid = $q->fetchColumn();
                $isTrainer = $tid ? true : false;
            }
        ?>
        <?php if ($isTrainer): ?>
            <form action="process_add_session.php" method="POST" style="display:grid; gap:8px; margin-top:10px;">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="formation_id" value="<?php echo $f['id']; ?>">
                <input type="datetime-local" name="start_time" required>
                <input type="url" name="link" placeholder="Lien de cours en ligne" required>
                <button type="submit" class="btn-outline" style="width:auto;">Ajouter une session</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
