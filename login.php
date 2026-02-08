<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - KAYOKA MBEKU NEWS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles modernes pour la connexion (cohérent avec l'inscription) */
        body {
            background-color: #f8f9fa;
        }
        
        .login-section {
            padding: 4rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px);
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            padding: 2rem; /* Réduit pour mobile */
            position: relative;
            border-top: 8px solid transparent;
            background-image: linear-gradient(#fff, #fff), linear-gradient(90deg, #0f766e, #3b82f6);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            margin: 0 15px; /* Marge latérale pour éviter d'être collé aux bords sur mobile */
        }
        
        @media (min-width: 768px) {
            .login-card {
                padding: 3rem;
                margin: 0;
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            background: linear-gradient(90deg, #0f766e, #3b82f6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .login-header p {
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

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #0f766e; /* Sarcelle */
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .register-link a:hover {
            color: #3b82f6; /* Bleu */
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="login-section">
        <div class="login-card">
            <div class="login-header">
                <h2>Connexion</h2>
                <p>Accédez à votre espace et au fil social.</p>
            </div>
            
            <form id="loginForm" action="auth_login.php" method="POST">
                <?php if (!empty($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email">Adresse Email</label>
                    <input type="email" id="email" name="email" required placeholder="Votre email">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="Votre mot de passe">
                    <div style="text-align: right; margin-top: 5px;">
                        <a href="forgot_password.php" style="font-size: 0.85rem; color: #0f766e; text-decoration:none;">Mot de passe oublié ?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">Se connecter</button>
                
                <div class="register-link">
                    <a href="register.php">Pas encore de compte ? Inscrivez-vous</a>
                </div>
            </form>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
        btn.disabled = true;

        // Supprimer les anciens messages d'erreur
        const existingAlert = form.querySelector('.alert');
        if (existingAlert) existingAlert.remove();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                // Afficher l'erreur
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert';
                alertDiv.style.backgroundColor = '#fee2e2';
                alertDiv.style.color = '#b91c1c';
                alertDiv.style.padding = '10px';
                alertDiv.style.borderRadius = '8px';
                alertDiv.style.marginBottom = '15px';
                alertDiv.style.fontSize = '0.9rem';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                
                // Insérer avant le premier champ
                const firstGroup = form.querySelector('.form-group');
                form.insertBefore(alertDiv, firstGroup);
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
    </script>
</body>
</html>
