<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: user_settings.php'); exit; }
$social_bg = $_POST['social_bg'] ?? '';
$messages_bg = $_POST['messages_bg'] ?? '';
$allowedKeys = ['','forest','ocean','sunset','photo1','photo2'];
if (!in_array($social_bg, $allowedKeys, true)) $social_bg = '';
if (!in_array($messages_bg, $allowedKeys, true)) $messages_bg = '';
$stmt = $pdo->prepare("INSERT INTO user_settings (user_id, social_bg, messages_bg) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE social_bg=VALUES(social_bg), messages_bg=VALUES(messages_bg)");
$stmt->execute([$uid, $social_bg ?: null, $messages_bg ?: null]);
    header('Location: user_settings.php'); exit;
}
$st = $pdo->prepare("SELECT social_bg, messages_bg FROM user_settings WHERE user_id=?");
$st->execute([$uid]);
$cur = $st->fetch(PDO::FETCH_ASSOC) ?: ['social_bg'=>null,'messages_bg'=>null];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres d'affichage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card-soft">
        <h2 class="section-title">Personnaliser l'arrière-plan</h2>
        <form action="" method="POST" style="display:grid; gap:10px;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
<label>Arrière-plan du Social</label>
<select name="social_bg">
    <?php $soc=$cur['social_bg']??''; ?>
    <option value="" <?php echo $soc===''?'selected':''; ?>>Par défaut</option>
    <option value="forest" <?php echo $soc==='forest'?'selected':''; ?>>Dégradé forêt</option>
    <option value="ocean" <?php echo $soc==='ocean'?'selected':''; ?>>Dégradé océan</option>
    <option value="sunset" <?php echo $soc==='sunset'?'selected':''; ?>>Dégradé coucher</option>
    <option value="photo1" <?php echo $soc==='photo1'?'selected':''; ?>>Photo 1</option>
    <option value="photo2" <?php echo $soc==='photo2'?'selected':''; ?>>Photo 2</option>
</select>
<label>Arrière-plan des Messages</label>
<select name="messages_bg">
    <?php $msgbg=$cur['messages_bg']??''; ?>
    <option value="" <?php echo $msgbg===''?'selected':''; ?>>Par défaut</option>
    <option value="forest" <?php echo $msgbg==='forest'?'selected':''; ?>>Dégradé forêt</option>
    <option value="ocean" <?php echo $msgbg==='ocean'?'selected':''; ?>>Dégradé océan</option>
    <option value="sunset" <?php echo $msgbg==='sunset'?'selected':''; ?>>Dégradé coucher</option>
    <option value="photo1" <?php echo $msgbg==='photo1'?'selected':''; ?>>Photo 1</option>
    <option value="photo2" <?php echo $msgbg==='photo2'?'selected':''; ?>>Photo 2</option>
</select>
            <button class="btn-submit" style="width:auto;">Enregistrer</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
