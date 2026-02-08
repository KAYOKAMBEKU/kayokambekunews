<?php
require_once 'db.php';

try {
    echo "Updating database schema...\n";

    // 1. Update users table role enum
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'formateur') DEFAULT 'user'");
        echo "Updated users role enum.\n";
    } catch (PDOException $e) {
        echo "Error updating users role: " . $e->getMessage() . "\n";
    }

    // 2. Add columns to trainers table
    $columns = [
        "bio" => "TEXT NULL",
        "competences" => "TEXT NULL",
        "diplomes" => "TEXT NULL",
        "formations_details" => "TEXT NULL"
    ];

    foreach ($columns as $name => $def) {
        try {
            $pdo->exec("ALTER TABLE trainers ADD COLUMN $name $def");
            echo "Added column $name to trainers.\n";
        } catch (PDOException $e) {
            // Ignore if column exists
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Column $name already exists.\n";
            } else {
                echo "Error adding column $name: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "Database update completed.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>