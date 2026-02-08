<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $ville_actuelle = trim($_POST['ville_actuelle'] ?? '');
    $pays = trim($_POST['pays'] ?? '');
    $nationalite = trim($_POST['nationalite'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $age = $_POST['age'] ?? '';
    $etat_civil = $_POST['etat_civil'] ?? '';
    $domain_id = $_POST['domain_id'] ?? null;
    $role = $_POST['role'] ?? 'user';
    if (!in_array($role, ['user', 'formateur'])) {
        $role = 'user';
    }

    // Validation basique
    if (empty($nom) || empty($password) || empty($telephone) || empty($domain_id) || empty($age)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
        exit;
    }

    // Validation Nom et Prénom (Lettres uniquement)
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
        echo json_encode(['success' => false, 'message' => 'Le nom ne doit contenir que des lettres.']);
        exit;
    }
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
        echo json_encode(['success' => false, 'message' => 'Le prénom ne doit contenir que des lettres.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse mail invalide.']);
        exit;
    }

    // Validation Mot de passe (Majuscule, minuscule, caractère spécial)
    if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[\W_])/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un caractère spécial.']);
        exit;
    }

    // Liste des nationalités/pays autorisés
    $allowed_nationalities = [
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

    if (!in_array($nationalite, $allowed_nationalities)) {
        echo json_encode(['success' => false, 'message' => 'Nationalité non reconnue. Veuillez sélectionner une nationalité valide.']);
        exit;
    }

    // Normalisation noms
    $nom = ucwords(strtolower($nom));
    $prenom = ucwords(strtolower($prenom));
    // Age minimal
    if (intval($age) < 10) {
        echo json_encode(['success' => false, 'message' => 'Âge minimal requis: 10 ans.']);
        exit;
    }
    // Téléphone format +XXXXXXXX
    if (!preg_match('/^\+\d{6,15}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => 'Téléphone invalide. Utilisez le format +codepaysnuméro, ex: +33612345678.']);
        exit;
    }
    try {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
            exit;
        }

        // Hash du mot de passe
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Création du champ 'name' pour compatibilité legacy
        $name = $nom . ' ' . $prenom;

        // Insertion (sans domain_id; l'association domaine se fait via user_domains)
        $sql = "INSERT INTO users (name, nom, prenom, lieu_naissance, ville_actuelle, pays, nationalite, telephone, email, password, age, etat_civil, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $nom, $prenom, $lieu_naissance, $ville_actuelle, $pays, $nationalite, $telephone, $email, $passwordHash, $age, $etat_civil, $role]);
        
        $userId = $pdo->lastInsertId();

        // Insertion Formateur
        if ($role === 'formateur') {
            $bio = trim($_POST['bio'] ?? '');
            $competences = trim($_POST['competences'] ?? '');
            $diplomes = trim($_POST['diplomes'] ?? '');
            $formations_details = trim($_POST['formations_details'] ?? '');
            
            $stmt = $pdo->prepare("INSERT INTO trainers (user_id, name, bio, competences, diplomes, formations_details) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $bio, $competences, $diplomes, $formations_details]);
            $trainerId = $pdo->lastInsertId();

            // Trainer subdomains
            if (isset($_POST['trainer_subdomains']) && is_array($_POST['trainer_subdomains'])) {
                $stmtSub = $pdo->prepare("INSERT INTO trainer_subdomains (trainer_id, sub_domain_id) VALUES (?, ?)");
                foreach ($_POST['trainer_subdomains'] as $sdId) {
                    try {
                        $stmtSub->execute([$trainerId, intval($sdId)]);
                    } catch (Exception $e) { /* Ignore dups */ }
                }
            }
        }

        // Insertion dans la table de liaison user_domains
        $stmt = $pdo->prepare("INSERT INTO user_domains (user_id, domain_id) VALUES (?, ?)");
        $stmt->execute([$userId, $domain_id]);

        // --- AJOUT AUTOMATIQUE AUX GROUPES ET SESSIONS EXISTANTS ---
        // Trouver toutes les formations liées à ce domaine (via sub_domains)
        // Et les ajouter aux groupes de discussion et enrollments
        try {
            $sql = "SELECT f.id as formation_id, f.trainer_id, g.id as group_id, f.user_id as creator_id
                    FROM formations f
                    JOIN sub_domains s ON f.sub_domain_id = s.id
                    LEFT JOIN chat_groups g ON f.id = g.formation_id
                    WHERE s.domain_id = ?";
            $stmtFormations = $pdo->prepare($sql);
            $stmtFormations->execute([$domain_id]);
            $formations = $stmtFormations->fetchAll();

            $stmtGroupInsert = $pdo->prepare("INSERT IGNORE INTO chat_group_members (group_id, user_id, is_admin, can_post) VALUES (?, ?, 0, 0)");
            $stmtEnrollInsert = $pdo->prepare("INSERT IGNORE INTO formation_enrollments (user_id, formation_id, status) VALUES (?, ?, 'active')");

            foreach ($formations as $f) {
                // Ajout au groupe de discussion (si le groupe existe)
                if (!empty($f['group_id'])) {
                    $stmtGroupInsert->execute([$f['group_id'], $userId]);
                }

                // Ajout à l'enrollment (pour apparaître dans "Mes Formations")
                // On considère que l'inscription par domaine donne accès (statut active)
                $stmtEnrollInsert->execute([$userId, $f['formation_id']]);
            }
        } catch (Exception $e) {
            // On continue même si cette étape échoue (ne pas bloquer l'inscription)
        }
        // -----------------------------------------------------------

        // Génération code de vérification
        $code = str_pad(strval(random_int(0,999999)), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time()+1800);
        $type = $email ? 'email' : 'phone';
        $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, type, purpose, expires_at) VALUES (?, ?, ?, 'register', ?)");
        $stmt->execute([$userId, $code, $type, $expires]);
        $_SESSION['pending_user_id'] = $userId;
        $_SESSION['verification_type'] = $type;
        
        // Stocker la redirection demandée pour après la vérification
        if (!empty($_POST['redirect'])) {
            $_SESSION['post_verify_redirect'] = $_POST['redirect'];
        }

        echo json_encode([
            'success' => true,
            'message' => "Inscription initiée. Code de vérification: $code",
            'redirect' => 'verify_code.php'
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
