<?php
require_once 'db.php';

$email = 'admin@kayoka.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Vérifier si l'admin existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Mise à jour
        $update = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE id = ?");
        $update->execute([$hash, $user['id']]);
        echo "Mot de passe de l'admin ($email) réinitialisé avec succès.<br>";
    } else {
        // Création
        $insert = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role, name, lieu_naissance, ville_actuelle, pays, nationalite, telephone, age, etat_civil) VALUES (?, ?, ?, ?, 'admin', ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute(['Admin', 'Principal', $email, $hash, 'Admin Principal', 'Paris', 'Paris', 'France', 'Française', '+243816762928', 30, 'Célibataire']);
        echo "Compte admin ($email) créé avec succès.<br>";
    }
    
    echo "Nouveau mot de passe : <strong>$password</strong><br>";
    echo "<a href='login.php'>Se connecter</a>";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>