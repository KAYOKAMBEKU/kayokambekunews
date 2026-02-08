<?php
$host = '127.0.0.1';
$dbname = 'kayokambekunews';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la base n'existe pas, on tente de se connecter sans le nom de la base pour la créer
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE $dbname");
        } catch (PDOException $ex) {
            die("Erreur de connexion : " . $ex->getMessage());
        }
    } else {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
