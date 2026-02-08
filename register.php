<?php
session_start();
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM domains ORDER BY name");
$domains = $stmt->fetchAll();

// Fetch subdomains for trainers
$stmt = $pdo->query("SELECT s.*, d.name as domain_name FROM sub_domains s JOIN domains d ON s.domain_id = d.id ORDER BY d.name, s.name");
$allSubdomains = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Group by domain_name doesn't work directly with join easily in one go unless I reformat.
// Let's just fetch all and group in PHP or just list them.
$stmt = $pdo->query("SELECT s.id, s.name, s.domain_id, d.name as domain_name FROM sub_domains s JOIN domains d ON s.domain_id = d.id ORDER BY d.name, s.name");
$rawSubdomains = $stmt->fetchAll();
$groupedSubdomains = [];
foreach ($rawSubdomains as $s) {
    $groupedSubdomains[$s['domain_name']][] = $s;
}

$countries = [
    'Afghanistan', 'Afrique du Sud', 'Algérie', 'Allemagne', 'Arabie Saoudite', 'Argentine', 'Australie', 'Autriche', 
    'Belgique', 'Bénin', 'Bolivie', 'Botswana', 'Brésil', 'Bulgarie', 'Burkina Faso', 'Burundi', 
    'Cameroun', 'Canada', 'Chili', 'Chine', 'Colombie', 'Congo', 'Côte d\'Ivoire', 'Danemark', 
    'Égypte', 'Émirats arabes unis', 'Espagne', 'États-Unis', 'Éthiopie', 'Finlande', 'France', 
    'Gabon', 'Ghana', 'Grèce', 'Guinée', 'Haïti', 'Hongrie', 'Inde', 'Indonésie', 'Irak', 'Iran', 'Irlande', 
    'Italie', 'Japon', 'Kenya', 'Liban', 'Libye', 'Madagascar', 'Malawi', 'Malaisie', 'Mali', 'Maroc', 
    'Mauritanie', 'Mexique', 'Mozambique', 'Namibie', 'Niger', 'Nigéria', 'Norvège', 'Nouvelle-Zélande', 
    'Ouganda', 'Pakistan', 'Pays-Bas', 'Philippines', 'Pologne', 'Portugal', 'Qatar', 
    'République Démocratique du Congo', 'Roumanie', 'Royaume-Uni', 'Russie', 'Rwanda', 
    'Sénégal', 'Serbie', 'Sierra Leone', 'Singapour', 'Somalie', 'Soudan', 'Suède', 'Suisse', 'Syrie', 
    'Tanzanie', 'Tchad', 'Thaïlande', 'Tunisie', 'Turquie', 'Ukraine', 'Uruguay', 'Venezuela', 'Vietnam', 
    'Zambie', 'Zimbabwe'
];

$nationalities = [
    'Afghane', 'Sud-Africaine', 'Algérienne', 'Allemande', 'Saoudienne', 'Argentine', 'Australienne', 'Autrichienne', 
    'Belge', 'Béninoise', 'Bolivienne', 'Botswanaise', 'Brésilienne', 'Bulgare', 'Burkinabè', 'Burundaise', 
    'Camerounaise', 'Canadienne', 'Chilienne', 'Chinoise', 'Colombienne', 'Congolaise (Brazzaville)', 'Ivoirienne', 'Danoise', 
    'Égyptienne', 'Émirienne', 'Espagnole', 'Américaine', 'Éthiopienne', 'Finlandaise', 'Française', 
    'Gabonaise', 'Ghanéenne', 'Grecque', 'Guinéenne', 'Haïtienne', 'Hongroise', 'Indienne', 'Indonésienne', 'Irakienne', 'Iranienne', 'Irlandaise', 
    'Italienne', 'Japonaise', 'Kenyane', 'Libanaise', 'Libyenne', 'Malgache', 'Malawite', 'Malaisienne', 'Malienne', 'Marocaine', 
    'Mauritanienne', 'Mexicaine', 'Mozambicaine', 'Namibienne', 'Nigérienne', 'Nigériane', 'Norvégienne', 'Néo-Zélandaise', 
    'Ougandaise', 'Pakistanaise', 'Néerlandaise', 'Philippine', 'Polonaise', 'Portugaise', 'Qatarienne', 
    'Congolaise (RDC)', 'Roumaine', 'Britannique', 'Russe', 'Rwandaise', 
    'Sénégalaise', 'Serbe', 'Sierra-léonaise', 'Singapourienne', 'Somalienne', 'Soudanaise', 'Suédoise', 'Suisse', 'Syrienne', 
    'Tanzanienne', 'Tchadienne', 'Thaïlandaise', 'Tunisienne', 'Turque', 'Ukrainienne', 'Uruguayenne', 'Vénézuélienne', 'Vietnamienne', 
    'Zambienne', 'Zimbabwéenne'
];
sort($nationalities);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - KAYOKA MBEKU NEWS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles spécifiques pour la page d'inscription moderne */
        body {
            background-color: #f8f9fa; /* Fond gris très clair */
        }
        
        .register-section {
            padding: 4rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px); /* Ajustement par rapport au header/footer */
        }

        .register-card {
            background: #ffffff;
            width: 100%;
            max-width: 900px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            padding: 3rem;
            position: relative;
            /* Bordure supérieure dégradée comme le titre du site */
            border-top: 8px solid transparent;
            background-image: linear-gradient(#fff, #fff), linear-gradient(90deg, #0f766e, #3b82f6);
            background-origin: border-box;
            background-clip: padding-box, border-box;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .register-header h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            /* Effet dégradé sur le texte comme le logo */
            background: linear-gradient(90deg, #0f766e, #3b82f6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .register-header p {
            color: #666;
            font-size: 1rem;
        }

        .register-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .register-grid {
                grid-template-columns: 1fr;
            }
            .register-card {
                padding: 1.5rem;
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-group input:focus, 
        .form-group select:focus {
            border-color: var(--primary-green);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
            outline: none;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .btn-register {
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
            margin-top: 1rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: var(--primary-green);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Section dividers */
        .form-section-title {
            grid-column: 1 / -1;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        /* Styles pour les cases à cocher */
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            background: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #eee;
            cursor: pointer;
            transition: all 0.2s;
            /* Assurer que le texte peut revenir à la ligne si nécessaire */
            white-space: normal;
            word-break: break-word;
            min-height: 40px; /* Hauteur minimale pour l'alignement */
        }

        .checkbox-item:hover {
            border-color: var(--primary-green);
            background: #f0fdfa;
        }

        .checkbox-item input {
            flex-shrink: 0; /* Empêcher la case à cocher de rétrécir */
            width: 16px;
            height: 16px;
            margin: 0 10px 0 0;
            cursor: pointer;
        }

        @media (max-width: 992px) {
             .checkbox-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .checkbox-grid {
                grid-template-columns: 1fr;
            }
            
            .checkbox-item {
                padding: 10px; /* Plus d'espace pour le tactile */
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="register-section">
        <div class="register-card">
            <div class="register-header">
                <h2>Créer un compte</h2>
                <p>Rejoignez la communauté KAYOKA MBEKU NEWS</p>
            </div>
            
            <form id="registerForm" action="process_register.php" method="POST" enctype="multipart/form-data">
                <?php if (!empty($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                <?php endif; ?>

                <div class="register-grid">
                    <!-- Informations Personnelles -->
                    <div class="form-section-title">Informations Personnelles</div>

                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                    </div>

                    <div class="form-group">
                        <label for="date_naissance">Âge</label>
                        <input type="number" id="age" name="age" placeholder="Ex: 25" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="etat_civil">État civil</label>
                        <select id="etat_civil" name="etat_civil" required>
                            <option value="">Sélectionnez...</option>
                            <option value="Célibataire">Célibataire</option>
                            <option value="Marié(e)">Marié(e)</option>
                            <option value="Divorcé(e)">Divorcé(e)</option>
                            <option value="Veuf/Veuve">Veuf/Veuve</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nationalite">Nationalité</label>
                        <select id="nationalite" name="nationalite" required>
                            <option value="">Sélectionnez...</option>
                            <?php foreach ($nationalities as $n): ?>
                                <option value="<?php echo htmlspecialchars($n); ?>"><?php echo htmlspecialchars($n); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="lieu_naissance">Lieu de naissance</label>
                        <input type="text" id="lieu_naissance" name="lieu_naissance" placeholder="Ville de naissance" required>
                    </div>

                    <!-- Localisation & Contact -->
                    <div class="form-section-title">Localisation & Contact</div>

                    <div class="form-group">
                        <label for="ville_actuelle">Ville actuelle</label>
                        <input type="text" id="ville_actuelle" name="ville_actuelle" placeholder="Ville de résidence" required>
                    </div>

                    <div class="form-group">
                        <label for="pays">Pays de résidence</label>
                        <select id="pays" name="pays" required>
                            <option value="">Sélectionnez un pays...</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone" placeholder="+243..." required>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse Email</label>
                        <input type="email" id="email" name="email" placeholder="exemple@email.com" required>
                    </div>

                    <!-- Compte & Intérêts -->
                    <div class="form-section-title">Compte & Intérêts</div>

                    <div class="form-group">
                        <label for="domain_id">Domaine d'intérêt principal</label>
                        <select id="domain_id" name="domain_id" required>
                            <option value="">Sélectionnez un domaine...</option>
                            <?php foreach ($domains as $domain): ?>
                                <option value="<?php echo $domain['id']; ?>"><?php echo htmlspecialchars($domain['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Type de compte</label>
                        <div style="display:flex; gap:20px; margin-top:5px;">
                            <label style="display:inline-flex; align-items:center; cursor:pointer;">
                                <input type="radio" name="role" value="user" checked onchange="toggleTrainerFields()" style="margin-right:8px;"> Utilisateur Simple
                            </label>
                            <label style="display:inline-flex; align-items:center; cursor:pointer;">
                                <input type="radio" name="role" value="formateur" onchange="toggleTrainerFields()" style="margin-right:8px;"> Formateur
                            </label>
                        </div>
                    </div>

                    <div id="trainer-fields" style="display:none; background:#f0f9ff; padding:15px; border-radius:8px; margin-bottom:15px; grid-column: 1 / -1;">
                        <h4 style="margin-top:0; color:#0f766e; margin-bottom:15px;">Informations Formateur</h4>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label for="bio">Biographie / Présentation</label>
                                <textarea id="bio" name="bio" rows="3" placeholder="Présentez-vous brièvement..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="competences">Compétences clés</label>
                                <textarea id="competences" name="competences" rows="3" placeholder="Ex: Gestion de projet, Leadership..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="diplomes">Diplômes & Certifications</label>
                                <textarea id="diplomes" name="diplomes" rows="3" placeholder="Vos diplômes et années d'obtention..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="formations_details">Expérience / Formations</label>
                                <textarea id="formations_details" name="formations_details" rows="3" placeholder="Formations suivies ou dispensées..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label>Domaines de compétence</label>
                            <div style="max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:10px; border-radius:4px; background:white;">
                                <?php foreach ($groupedSubdomains as $domainName => $subs): ?>
                                    <strong style="display:block; margin-top:5px; margin-bottom:5px; color:#333; border-bottom:1px solid #eee; padding-bottom:2px;"><?php echo htmlspecialchars($domainName); ?></strong>
                                    <div class="checkbox-grid">
                                        <?php foreach ($subs as $sub): ?>
                                            <label class="checkbox-item" style="cursor:pointer;">
                                                <input type="checkbox" name="trainer_subdomains[]" value="<?php echo $sub['id']; ?>"> 
                                                <?php echo htmlspecialchars($sub['name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Choisissez un mot de passe sécurisé" required>
                    </div>

                    <div class="full-width">
                        <button type="submit" class="btn-register">Créer mon compte</button>
                    </div>
                </div>

            </form>
            
            <div class="login-link">
                Déjà un compte ? <a href="login.php">Connectez-vous</a>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
    function toggleTrainerFields() {
        const role = document.querySelector('input[name="role"]:checked').value;
        const fields = document.getElementById('trainer-fields');
        if (role === 'formateur') {
            fields.style.display = 'block';
        } else {
            fields.style.display = 'none';
        }
    }
    
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription...';
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
                // Redirection ou affichage message succès
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                }
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
                
                // Insérer après le header
                const header = form.querySelector('.register-header') || form.querySelector('h2'); // Fallback
                if (header && header.nextElementSibling) {
                    header.parentNode.insertBefore(alertDiv, header.nextElementSibling);
                } else {
                    form.insertBefore(alertDiv, form.firstChild);
                }
                
                // Scroll to top
                window.scrollTo({ top: form.offsetTop - 50, behavior: 'smooth' });
                
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
