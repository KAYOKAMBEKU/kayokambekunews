<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

$token = $_GET['token'] ?? '';
$valid = false;
if ($token) {
    $stmt = $pdo->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    $valid = (bool)$row;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container" style="padding:2rem 0;">
        <div class="card form-container" style="max-width:600px;margin:0 auto;">
            <h2>Réinitialiser le mot de passe</h2>
            <?php if (!$valid): ?>
                <p style="color:red;">Lien invalide ou expiré.</p>
            <?php else: ?>
                <form action="process_reset_password.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn-submit" style="width:auto;">Réinitialiser</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
