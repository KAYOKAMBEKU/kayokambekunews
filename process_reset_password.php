<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    header('Location: reset_password.php?token=' . urlencode($_POST['token'] ?? '') . '&error=' . urlencode('Session expirée, veuillez réessayer.'));
    exit;
}

$token = $_POST['token'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($new !== $confirm) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Les mots de passe ne correspondent pas.'));
    exit;
}

if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Mot de passe trop faible (8+ caractères, lettres et chiffres).'));
    exit;
}

$stmt = $pdo->prepare("SELECT pr.*, u.id as uid FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Lien invalide ou expiré.'));
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $row['uid']]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$row['id']]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Erreur serveur, réessayez plus tard.'));
    exit;
}

header('Location: login.php?msg=' . urlencode('Mot de passe réinitialisé, vous pouvez vous connecter.'));
exit;
