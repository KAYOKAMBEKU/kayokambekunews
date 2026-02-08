<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: formations.php'); exit; }
$subs = $pdo->query("SELECT sd.id, sd.name, d.name as dname FROM sub_domains sd JOIN domains d ON d.id=sd.domain_id ORDER BY d.name, sd.name")->fetchAll();
$trainers = $pdo->query("SELECT id, name FROM trainers ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une formation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card-soft">
        <h2 class="section-title">Ajouter une formation</h2>
        <form action="process_add_formation.php" method="POST" style="display:grid; gap:10px;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <select name="sub_domain_id" required>
                <option value="">Choisir un sous-domaine</option>
                <?php foreach ($subs as $sd): ?>
                    <option value="<?php echo $sd['id']; ?>"><?php echo htmlspecialchars($sd['dname'].' / '.$sd['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="trainer_id" required>
                <option value="">Choisir un formateur</option>
                <?php foreach ($trainers as $tr): ?>
                    <option value="<?php echo $tr['id']; ?>"><?php echo htmlspecialchars($tr['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="title" placeholder="Titre de la formation" required>
            <textarea name="description" placeholder="Description (optionnel)" rows="4"></textarea>
            <input type="number" step="0.01" name="price" placeholder="Prix (USD) - Laisser vide pour gratuit">
            <input type="url" name="resource_url" placeholder="Lien ressource (vidéo/document) - optionnel">
            
            <label>Date de début (Laisser vide pour "Immédiat")</label>
            <input type="datetime-local" name="start_time">
            
            <!-- Champs masqués par défaut pour simplifier -->
            <input type="hidden" name="end_time" value="">
            <input type="hidden" name="status" value="published">
            
            <button type="submit" class="btn-submit" style="width:auto;">Publier maintenant</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
