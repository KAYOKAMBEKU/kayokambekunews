<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'])) {
        die("Erreur CSRF");
    }

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $image_url = null;

    if (empty($title) || empty($content)) {
        die("Veuillez remplir tous les champs obligatoires.");
    }

    // Gestion de l'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newName = 'article_' . uniqid() . '.' . $ext;
            $uploadDir = 'uploads/articles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $image_url = $uploadDir . $newName;
            }
        }
    }

    $mediaType = $image_url ? 'image' : null;
    // Utilisation de la table 'posts' au lieu de 'articles' (table unifiée)
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, media_type, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    if ($stmt->execute([$user_id, $title, $content, $image_url, $mediaType])) {
        header('Location: index.php?msg=article_published');
        exit;
    } else {
        echo "Erreur lors de la publication.";
    }
}
?>