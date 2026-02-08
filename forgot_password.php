<?php
session_start();
require_once __DIR__ . '/helpers/csrf.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container" style="padding:2rem 0;">
        <div class="card form-container" style="max-width:600px;margin:0 auto;">
            <h2>Mot de passe oublié</h2>
            <?php if (isset($_GET['msg'])): ?>
                <p style="color:green;"><?php echo htmlspecialchars($_GET['msg']); ?></p>
            <?php endif; ?>
            <form action="process_forgot_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <label>Votre email</label>
                    <input type="email" name="email" required>
                </div>
                <button type="submit" class="btn-submit" style="width:auto;">Envoyer le lien de réinitialisation</button>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
