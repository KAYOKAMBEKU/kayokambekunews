<?php
session_start();
require_once 'db.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($name === '' || $content === '') {
        $error = "Veuillez renseigner votre nom et votre message.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO feedback (visitor_name, content) VALUES (?, ?)");
        $stmt->execute([$name, $content]);
        $success = "Votre message a été envoyé à l'administrateur. Merci !";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacter l’Admin - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container" style="padding:2rem 0;">
        <h2 class="section-title"><i class="fas fa-envelope"></i> Contacter l’Administrateur</h2>
        <div class="card form-container">
            <?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <?php if ($success): ?><p style="color:green;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="content" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Envoyer</button>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
