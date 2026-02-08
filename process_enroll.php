<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: formations.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: formations.php'); exit; }

$uid = $_SESSION['user_id'];
$fid = intval($_POST['formation_id'] ?? 0);
$method = $_POST['method'] ?? '';
$number = trim($_POST['payer_number'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);

if ($fid > 0 && in_array($method, ['mpesa','airtel','orange']) && $amount > 0 && !empty($number)) {
    // 1. Create Enrollment (Pending)
    $stmt = $pdo->prepare("INSERT IGNORE INTO formation_enrollments (user_id, formation_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$uid, $fid]);
    
    // 2. Record Payment Attempt (Pending)
    // We use the submitted amount, not just the fixed price
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, formation_id, method, payer_number, amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$uid, $fid, $method, $number, $amount]);
    
    // 3. Simulate USSD Push Interface
    // Get formation title for display
    $stmt = $pdo->prepare("SELECT title FROM formations WHERE id=?");
    $stmt->execute([$fid]);
    $ftitle = $stmt->fetchColumn();
    
    $provider_colors = [
        'mpesa' => '#e60000', // Vodacom Red
        'airtel' => '#ff0000', // Airtel Red
        'orange' => '#ff6600' // Orange
    ];
    $color = $provider_colors[$method] ?? '#333';
    $provider_name = ucfirst($method);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement en cours...</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--brand);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .ussd-screen {
            background: #000;
            color: #0f0;
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container section" style="max-width: 600px; text-align: center; margin-top: 50px;">
        <div class="card">
            <div style="font-size: 3rem; color: <?php echo $color; ?>; margin-bottom: 1rem;">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h2>Demande de paiement envoyée</h2>
            <p>Veuillez consulter votre téléphone <strong><?php echo htmlspecialchars($number); ?></strong>.</p>
            
            <div class="ussd-screen">
                > <?php echo $provider_name; ?><br>
                > Confirmer paiement de <?php echo number_format($amount, 2); ?> USD<br>
                > Pour: KAYOKA ACADEMY<br>
                > Entrez votre PIN: _
            </div>

            <div class="loader"></div>
            
            <p class="text-muted">En attente de votre validation sur le mobile...</p>
            
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
            
            <!-- Simulation Button -->
            <a href="verify_payment_simulation.php?fid=<?php echo $fid; ?>&uid=<?php echo $uid; ?>" class="btn-submit">
                <i class="fas fa-check-circle"></i> J'ai validé le paiement
            </a>
            <br><br>
            <a href="enroll_formation.php?id=<?php echo $fid; ?>" class="btn-outline" style="border:none;">Annuler / Réessayer</a>
        </div>
    </div>
</body>
</html>
<?php
    exit;
} else {
    // Error
    header('Location: enroll_formation.php?id='.$fid.'&error=invalid_data');
    exit;
}
?>
