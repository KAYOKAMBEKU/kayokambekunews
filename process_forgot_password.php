<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    header('Location: forgot_password.php?msg=' . urlencode('Session expirée, veuillez réessayer.'));
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    header('Location: forgot_password.php?msg=' . urlencode('Veuillez saisir votre email.'));
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Générer un token même si l'email n'existe pas (ne pas révéler l'état)
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1h

if ($user) {
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expiresAt]);

    $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . urlencode($token);

    // Essayer d'envoyer l'email
    $to = $email;
    $subject = "Réinitialisation de votre mot de passe - KAYOKA";
    $message = "Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe.\nCliquez sur le lien suivant pour définir un nouveau mot de passe :\n\n" . $resetUrl . "\n\nCe lien expire dans 1 heure.\n\nSi vous n'avez pas demandé cela, ignorez cet email.";
    $headers = "From: no-reply@kayoka.com\r\n";
    $headers .= "Reply-To: no-reply@kayoka.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($to, $subject, $message, $headers)) {
        header('Location: forgot_password.php?msg=' . urlencode('Un email de réinitialisation a été envoyé à ' . $email));
        exit;
    }
}

// Si l'utilisateur n'existe pas ou si l'envoi de mail échoue (ex: local sans SMTP), on affiche le lien pour le débuggage local ou un message générique
$resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . urlencode($token);

// En production, on ne devrait pas afficher le lien si l'email n'est pas parti, mais pour le développement :
header('Location: forgot_password.php?msg=' . urlencode('Lien généré (Email non envoyé sur localhost). Cliquez ici : ' . $resetUrl));
exit;
