<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['pending_user_id'])) { header('Location: register.php'); exit; }
$userId = $_SESSION['pending_user_id'];
$type = $_SESSION['verification_type'] ?? 'email';
$code = trim($_POST['code'] ?? '');

if ($code === '') { header('Location: verify_code.php'); exit; }

$stmt = $pdo->prepare("SELECT id FROM verification_codes WHERE user_id = ? AND code = ? AND type = ? AND purpose = 'register' AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
$stmt->execute([$userId, $code, $type]);
$row = $stmt->fetch();
if (!$row) { header('Location: verify_code.php'); exit; }

$pdo->beginTransaction();
try {
    if ($type === 'email') {
        $pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?")->execute([$userId]);
    } else {
        $pdo->prepare("UPDATE users SET phone_verified_at = NOW() WHERE id = ?")->execute([$userId]);
    }
    $pdo->prepare("UPDATE verification_codes SET used_at = NOW() WHERE id = ?")->execute([$row['id']]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: verify_code.php'); exit;
}

unset($_SESSION['pending_user_id'], $_SESSION['verification_type']);

$stmt = $pdo->prepare("SELECT role, nom, prenom FROM users WHERE id = ?");
$stmt->execute([$userId]);
$u = $stmt->fetch();
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $u['prenom'].' '.$u['nom'];
$_SESSION['user_role'] = $u['role'];

$redirect = 'dashboard.php';
if (isset($_SESSION['post_verify_redirect'])) {
    $redirect = $_SESSION['post_verify_redirect'];
    unset($_SESSION['post_verify_redirect']);
} elseif (!empty($_POST['redirect'])) {
    // Au cas où le paramètre passerait en POST
    $redirect = $_POST['redirect'];
}

header('Location: ' . $redirect);
exit;
