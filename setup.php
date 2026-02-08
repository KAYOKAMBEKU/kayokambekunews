<?php
require 'db.php';

$sql = file_get_contents('database.sql');

try {
    $pdo->exec($sql);
    echo "Base de données mise à jour avec succès.\n";
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour : " . $e->getMessage() . "\n";
}
?>
