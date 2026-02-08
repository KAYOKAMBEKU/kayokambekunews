<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!csrf_verify($token)) {
    header('Location: edit_profile.php?error=' . urlencode('Session expirée, veuillez réessayer.'));
    exit;
}
$userId = $_SESSION['user_id'];
$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
$ville = trim($_POST['ville_actuelle']);
$pays = trim($_POST['pays']);
$telephone = trim($_POST['telephone']);
$email = trim($_POST['email']);
$age = $_POST['age'];
$etat_civil = $_POST['etat_civil'];

// Mise à jour de la photo de profil si fournie (avec crop basique)
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_pic']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $newFilename = 'profile_' . $userId . '_' . time() . '.' . $ext;
        if (!is_dir('uploads/profiles')) {
            mkdir('uploads/profiles', 0777, true);
        }
        $tmp = $_FILES['profile_pic']['tmp_name'];
        $dest = 'uploads/profiles/' . $newFilename;
        move_uploaded_file($tmp, $dest);
        // Crop 300x300 avec zoom et offset (fallback si GD non disponible)
        $zoom = isset($_POST['avatar_zoom']) ? floatval($_POST['avatar_zoom']) : 1.0;
        $offX = isset($_POST['avatar_x']) ? intval($_POST['avatar_x']) : 0;
        $offY = isset($_POST['avatar_y']) ? intval($_POST['avatar_y']) : 0;
        $canGD = function_exists('imagecreatetruecolor');
        if ($canGD) {
            $srcImg = null;
            if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) $srcImg = imagecreatefromjpeg($dest);
            elseif ($ext === 'png' && function_exists('imagecreatefrompng')) $srcImg = imagecreatefrompng($dest);
            elseif ($ext === 'gif' && function_exists('imagecreatefromgif')) $srcImg = imagecreatefromgif($dest);
            if ($srcImg) {
                $w = imagesx($srcImg); $h = imagesy($srcImg);
                $dstW = 300; $dstH = 300;
                $dst = imagecreatetruecolor($dstW, $dstH);
                imagecopyresampled($dst, $srcImg, -$offX, -$offY, 0, 0, $dstW, $dstH, $w, $h);
                if ($ext === 'jpg' || $ext === 'jpeg') imagejpeg($dst, $dest, 90);
                elseif ($ext === 'png') imagepng($dst, $dest);
                elseif ($ext === 'gif') imagegif($dst, $dest);
                imagedestroy($dst); imagedestroy($srcImg);
            }
        }
        $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$newFilename, $userId]);
    }
}

// Photo de couverture
if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['cover_photo']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $newFilename = 'cover_' . $userId . '_' . time() . '.' . $ext;
        if (!is_dir('uploads/covers')) { mkdir('uploads/covers', 0777, true); }
        if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], 'uploads/covers/' . $newFilename)) {
            $stmt = $pdo->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
            $stmt->execute([$newFilename, $userId]);
        }
    }
}

// Mise à jour des infos
$sql = "UPDATE users SET nom = ?, prenom = ?, ville_actuelle = ?, pays = ?, telephone = ?, email = ?, age = ?, etat_civil = ?, name = ? WHERE id = ?";
$fullName = $nom . ' ' . $prenom;
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([$nom, $prenom, $ville, $pays, $telephone, $email, $age, $etat_civil, $fullName, $userId]);
    $_SESSION['user_name'] = $fullName; // Mise à jour session
    header('Location: dashboard.php?msg=updated');
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour : " . $e->getMessage());
}
?>
