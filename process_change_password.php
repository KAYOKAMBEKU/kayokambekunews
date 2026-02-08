<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: change_password.php');
    exit;
}

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!csrf_verify($token)) {
    header('Location: change_password.php?error=' . urlencode('Session expirée, veuillez réessayer.'));
    exit;
}

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($new !== $confirm) {
    header('Location: change_password.php?error=' . urlencode('Les mots de passe ne correspondent pas.'));
    exit;
}

// Validation complexité minimale
if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
    header('Location: change_password.php?error=' . urlencode('Mot de passe trop faible (8+ caractères, lettres et chiffres).'));
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: change_password.php?error=' . urlencode('Utilisateur introuvable.'));
    exit;
}

if (!password_verify($current, $user['password'])) {
    header('Location: change_password.php?error=' . urlencode('Mot de passe actuel incorrect.'));
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hash, $userId]);

header('Location: change_password.php?success=' . urlencode('Mot de passe mis à jour avec succès.'));
exit;
