<?php
require_once 'db.php';

try {
    echo "Updating formations table...\n";

    try {
        $pdo->exec("ALTER TABLE formations ADD COLUMN trainer_id INT NULL");
        $pdo->exec("ALTER TABLE formations ADD CONSTRAINT fk_formation_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL");
        echo "Added trainer_id to formations.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column trainer_id already exists.\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>