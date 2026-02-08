<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, password, role, telephone FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                try {
                    $stmt2 = $pdo->prepare("SELECT email_verified_at FROM users WHERE id = ?");
                    $stmt2->execute([$user['id']]);
                    $ver = $stmt2->fetch();
                    if (isset($ver['email_verified_at']) && !$ver['email_verified_at'] && $user['email']) {
                        echo json_encode(['success' => false, 'message' => 'Email non vérifié. Veuillez terminer la vérification.']);
                        exit;
                    }
                } catch (PDOException $e) {
                    // Colonne absente -> compatibilité rétro: ne pas bloquer la connexion
                }
            }
            // Authentification réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_role'] = $user['role'];

            $redirect = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php';
            
            // Check for explicit redirect request
            if (!empty($_POST['redirect'])) {
                $redirect = $_POST['redirect'];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie.',
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
