<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$userId = $_SESSION['user_id'];
$formationId = intval($_POST['formation_id'] ?? 0);
$type = $_POST['type'] ?? '';
$title = trim($_POST['title'] ?? '');

if ($formationId <= 0 || empty($title) || !in_array($type, ['document', 'video'])) {
    die("Données invalides.");
}

// Verify permission (Trainer or Admin)
$stmt = $pdo->prepare("SELECT trainer_id FROM formations WHERE id = ?");
$stmt->execute([$formationId]);
$f = $stmt->fetch();

if (!$f) { die("Formation introuvable."); }

$isTrainer = ($f['trainer_id'] == $userId);
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

if (!$isTrainer && !$isAdmin) {
    die("Accès refusé.");
}

// Handle File Upload
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $allowedExts = [];
    if ($type === 'document') {
        $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
    } else {
        $allowedExts = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    }

    $fileName = $_FILES['file']['name'];
    $fileTmp = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        die("Type de fichier non autorisé.");
    }

    $newName = uniqid() . '.' . $ext;
    $dest = 'uploads/course_materials/' . $newName;

    if (move_uploaded_file($fileTmp, $dest)) {
        $stmtIns = $pdo->prepare("INSERT INTO course_materials (formation_id, user_id, type, title, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->execute([$formationId, $userId, $type, $title, $dest]);
        header("Location: formation_view.php?id=" . $formationId);
        exit;
    } else {
        die("Erreur lors de l'upload.");
    }
} else {
    die("Aucun fichier sélectionné ou erreur d'upload.");
}
?>
