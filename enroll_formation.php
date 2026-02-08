<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
$f = null;
if ($id>0) {
    $stmt = $pdo->prepare("SELECT * FROM formations WHERE id=?");
    $stmt->execute([$id]);
    $f = $stmt->fetch();
}
if (!$f) { header('Location: formations.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S'inscrire à la formation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card-soft">
        <h2 class="section-title">Inscription à la formation</h2>
        <p><strong><?php echo htmlspecialchars($f['title']); ?></strong></p>
        <p>Montant officiel: <strong><?php echo number_format($f['price'],2); ?> USD</strong></p>
        <form action="process_enroll.php" method="POST" style="display:grid; gap:15px;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="formation_id" value="<?php echo $f['id']; ?>">
            
            <div class="form-group">
                <label>Montant à payer (USD)</label>
                <input type="number" name="amount" value="<?php echo $f['price']; ?>" step="0.01" min="1" required>
            </div>

            <div class="form-group">
                <label>Numéro de téléphone (pour validation USSD)</label>
                <input type="tel" name="payer_number" placeholder="Ex: 0990000000" required>
                <small class="text-muted" style="font-size:0.85rem;">Le code de validation sera envoyé à ce numéro.</small>
            </div>

            <div class="form-group">
                <label>Moyen de paiement</label>
                <select name="method" required>
                    <option value="">Choisir l'opérateur</option>
                    <option value="mpesa">M-Pesa (Vodacom)</option>
                    <option value="airtel">Airtel Money</option>
                    <option value="orange">Orange Money</option>
                </select>
            </div>

            <button type="submit" class="btn-submit" style="width:auto;">Confirmer et Payer</button>
        </form>
        <p class="text-muted" style="margin-top:10px; font-size:0.9rem;">Une fois confirmé, vérifiez votre téléphone pour saisir votre code PIN.</p>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
