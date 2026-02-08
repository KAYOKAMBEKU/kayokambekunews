<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['pending_user_id'])) { header('Location: register.php'); exit; }
$type = $_SESSION['verification_type'] ?? 'email';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du compte</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles modernes (cohérent avec login/inscription) */
        body {
            background-color: #f8f9fa;
        }
        
        .auth-section {
            padding: 4rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px);
        }

        .auth-card {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            padding: 3rem;
            position: relative;
            border-top: 8px solid transparent;
            background-image: linear-gradient(#fff, #fff), linear-gradient(90deg, #0f766e, #3b82f6);
            background-origin: border-box;
            background-clip: padding-box, border-box;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            background: linear-gradient(90deg, #0f766e, #3b82f6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .auth-header p {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-group input:focus {
            border-color: var(--primary-green);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
            outline: none;
        }

        .btn-auth {
            background: linear-gradient(90deg, #0f766e, #3b82f6);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <section class="auth-section">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Vérification (<?php echo htmlspecialchars($type); ?>)</h2>
                <p>Veuillez saisir le code de vérification reçu.</p>
            </div>
            
            <form action="process_verify_code.php" method="POST">
                <?php if (!empty($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Code à 6 chiffres</label>
                    <input type="text" name="code" required maxlength="6" placeholder="Ex: 123456" style="text-align:center; letter-spacing: 5px; font-size: 1.2rem;">
                </div>
                
                <button type="submit" class="btn-auth">Vérifier</button>
            </form>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
