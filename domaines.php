<?php
session_start();
require_once 'db.php';

// Récupérer Domaines et Sous-domaines
$stmt = $pdo->query("SELECT * FROM domains");
$domains = $stmt->fetchAll();

// Récupérer les sous-domaines
$subDomains = [];
$stmt = $pdo->query("SELECT * FROM sub_domains");
while ($row = $stmt->fetch()) {
    $subDomains[$row['domain_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domaines - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="padding: 2rem 0;">
        <h2 class="section-title">Nos Domaines d'Activité</h2>

        <div class="grid-3">
            <?php foreach ($domains as $domain): ?>
            <div class="card" style="text-align: center;">
                <a href="domain.php?id=<?php echo $domain['id']; ?>" style="text-decoration:none; color:inherit;">
                    <i class="<?php echo $domain['icon']; ?>" style="font-size: 3rem; color: var(--primary-green); margin-bottom: 1rem;"></i>
                    <h3><?php echo htmlspecialchars($domain['name']); ?></h3>
                </a>
                <p style="margin: 1rem 0; color: #666;"><?php echo htmlspecialchars($domain['description']); ?></p>
                
                <div style="text-align: left; background: #f9f9f9; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <strong>Sous-catégories:</strong>
                    <ul style="list-style: none; margin-top: 5px;">
                        <?php if (isset($subDomains[$domain['id']])): ?>
                            <?php foreach ($subDomains[$domain['id']] as $sub): ?>
                                <li><i class="fas fa-angle-right" style="color: var(--accent-yellow);"></i> <a href="sub_domain.php?id=<?php echo $sub['id']; ?>" style="text-decoration:none;"><?php echo htmlspecialchars($sub['name']); ?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
