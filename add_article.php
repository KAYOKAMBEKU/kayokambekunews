<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

// Rediriger si non connecté
if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publier un article</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card-soft">
        <h2 class="section-title">Publier un article</h2>
        <form action="process_add_article.php" method="POST" enctype="multipart/form-data" style="display:grid; gap:10px;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="form-group">
                <label for="title">Titre de l'article</label>
                <input type="text" id="title" name="title" placeholder="Titre accrocheur..." required>
            </div>
            
            <div class="form-group">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" placeholder="Rédigez votre article ici..." rows="8" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Image de couverture (Optionnel)</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <button type="submit" class="btn-submit">Publier l'article</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>