<?php
session_start();
require_once 'db.php';

// Récupérer toutes les news
$stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
$newsList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualités - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container-narrow section">
        <h2 class="section-title">Toutes les Actualités</h2>

        <?php if (empty($newsList)): ?>
            <div class="card-soft" style="text-align:center;">Aucune actualité pour le moment.</div>
        <?php else: ?>
            <div class="grid-3">
                <?php foreach ($newsList as $news): ?>
                <div class="card news-card">
                    <?php if (!empty($news['image'])): ?>
                        <img src="<?php echo htmlspecialchars($news['image']); ?>" class="responsive-img" style="object-fit: cover; border-radius: 10px; max-height: 180px;">
                    <?php endif; ?>
                    <h3 style="margin-top: 0.75rem;"><?php echo htmlspecialchars($news['title']); ?></h3>
                    <div class="stack">
                        <span class="news-date"><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($news['created_at'])); ?></span>
                        <p><?php echo nl2br(htmlspecialchars(mb_strimwidth($news['content'], 0, 160, '…'))); ?></p>
                        <?php
                            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                            $shareText = $news['title'] . " - Voir sur KAYOKA: " . $baseUrl . "/actualites.php";
                            $waHref = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
                        ?>
                        <div style="margin-top:10px;">
                            <a href="<?php echo $waHref; ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">
                                <i class="fab fa-whatsapp"></i> Partager sur WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
