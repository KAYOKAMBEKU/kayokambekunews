<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - KAYOKA MBEKU NEWS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-landing {
            background: linear-gradient(rgba(15,23,42,0.8), rgba(15,23,42,0.8)), url('img/backgroung.png') no-repeat center center/cover;
            min-height: 70vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            justify-content: center;
            text-align: left;
            color: white;
            padding: 40px;
            gap: 40px;
        }
        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .hero-visual img {
            max-width: 80%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .hero-panel {
            padding-right: 20px;
        }
        .hero-landing h1 {
            font-size: clamp(2.5rem, 6vw, 4rem);
            margin-bottom: 1.5rem;
            text-shadow: 1px 2px 4px rgba(0,0,0,0.4);
            line-height: 1.1;
        }
        .hero-landing p {
            font-size: clamp(1.1rem, 3.5vw, 1.3rem);
            margin-bottom: 2.5rem;
            max-width: 100%;
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            .hero-landing {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 30px 20px;
                gap: 20px;
            }
            .hero-panel {
                padding-right: 0;
                order: 2;
            }
            .hero-visual {
                order: 1;
                margin-bottom: 10px;
            }
            .hero-visual img {
                max-width: 50%;
            }
        }
        .btn-landing {
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px;
            display: inline-block;
            transition: transform 0.3s;
        }
        .btn-landing:hover { transform: scale(1.05); }
        .btn-primary { background: var(--accent-yellow); color: var(--primary-green); }
        .btn-secondary { background: transparent; border: 0; color: white; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="hero-landing">
        <div class="hero-visual">
            <img src="img/logo.jpg" alt="KAYOKA Logo">
        </div>
        <div class="hero-panel">
            <h1>K M N</h1>
            <p>Plateforme communautaire d’information et d’expression. Partagez, échangez et suivez les actualités avec une expérience fluide et professionnelle.</p>
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn-landing btn-ghost-white">Rejoindre la communauté</a>
                <a href="login.php" class="btn-landing btn-ghost-white">Se connecter</a>
            <?php else: ?>
                <a href="social.php" class="btn-landing btn-ghost-white">Accéder au Fil Social</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'articles_carousel.php'; ?>

    <!-- Section Mobile QR -->
    <section class="section" style="background: var(--bg); padding: 40px 0;">
        <div class="container">
            <div class="card" style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 40px; padding: 40px; background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: white; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <h2 style="font-size: 2rem; margin-bottom: 15px; color: #fff;">Emportez KAYOKA partout !</h2>
                    <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 25px;">
                        Accédez rapidement à la plateforme depuis votre mobile. Scannez simplement ce code QR pour être redirigé instantanément vers notre site optimisé pour smartphone.
                    </p>
                    <div style="display: flex; gap: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 10px;">
                            <i class="fas fa-mobile-alt" style="font-size: 1.5rem;"></i>
                            <span>100% Compatible Mobile</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 10px;">
                            <i class="fas fa-bolt" style="font-size: 1.5rem;"></i>
                            <span>Accès Rapide</span>
                        </div>
                    </div>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <?php
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        $uri = $_SERVER['REQUEST_URI'];
                        $currentUrl = $protocol . "://" . $host . $uri;
                        // Nettoyer l'URL pour pointer vers la racine si besoin, mais ici l'accueil est parfait
                    ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode($currentUrl); ?>&color=0f766e" alt="QR Code KAYOKA" style="width: 180px; height: 180px; display: block; margin-bottom: 10px;">
                    <span style="color: var(--brand); font-weight: bold; font-size: 0.9rem;">Scannez-moi</span>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
