<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$fields = ['email','telephone','ville_actuelle','pays','age','etat_civil'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: privacy_settings.php'); exit; }
    foreach ($fields as $f) {
        $v = $_POST['vis_'.$f] ?? 'public';
        if (!in_array($v, ['public','friends','private'])) $v='public';
        $stmt = $pdo->prepare("INSERT INTO user_privacy (user_id, field, visibility) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE visibility=VALUES(visibility)");
        $stmt->execute([$uid, $f, $v]);
    }
    header('Location: privacy_settings.php'); exit;
}
$vis = [];
$st = $pdo->prepare("SELECT field, visibility FROM user_privacy WHERE user_id=?");
$st->execute([$uid]);
foreach ($st->fetchAll() as $row) { $vis[$row['field']]=$row['visibility']; }
function sel($cur,$val){ return ($cur===$val)?'selected':''; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confidentialité du profil</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card-soft">
        <h2 class="section-title">Paramétrer la confidentialité</h2>
        <form action="" method="POST" style="display:grid; gap:10px;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <?php foreach ($fields as $f): $cur=$vis[$f]??'public'; ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <label style="width:180px;"><?php echo htmlspecialchars($f); ?></label>
                    <select name="vis_<?php echo htmlspecialchars($f); ?>">
                        <option value="public" <?php echo sel($cur,'public'); ?>>Public</option>
                        <option value="friends" <?php echo sel($cur,'friends'); ?>>Amis</option>
                        <option value="private" <?php echo sel($cur,'private'); ?>>Privé</option>
                    </select>
                </div>
            <?php endforeach; ?>
            <button class="btn-submit" style="width:auto;">Enregistrer</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
