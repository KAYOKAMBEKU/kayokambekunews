<?php
require_once 'db.php';

try {
    echo "Creating Group tables...\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            formation_id INT NULL,
            name VARCHAR(255) NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_group_members (
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            can_post BOOLEAN DEFAULT FALSE,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_group_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            sender_id INT NOT NULL,
            content TEXT NOT NULL,
            attachment_path VARCHAR(255) DEFAULT NULL,
            attachment_type ENUM('image','document','audio') DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    echo "Group tables created.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>