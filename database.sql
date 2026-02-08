CREATE DATABASE IF NOT EXISTS kayokambekunews CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kayokambekunews;


-- Désactiver les vérifications de clés étrangères pour permettre la reconstruction
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. TABLES RESTAURÉES (ANCIEN SYSTÈME)
-- ==========================================

-- Table des Domaines
CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des Sous-Domaines
CREATE TABLE IF NOT EXISTS sub_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
);

-- Table des Actualités (News Admin - Ancien système)
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des Messages (Contact - Ancien système)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) DEFAULT 'Contact',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 2. NOUVELLES TABLES & MODIFICATIONS
-- ==========================================

-- Table des Utilisateurs (Étendu avec nouveaux champs + rétro-compatibilité)
-- On recrée la table pour inclure tous les champs demandés proprement
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- Anciens champs (pour compatibilité)
    name VARCHAR(255) NOT NULL, -- Sera concaténation Nom + Prénom ou juste Nom
    -- Nouveaux champs demandés
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NULL,
    lieu_naissance VARCHAR(100) NOT NULL,
    ville_actuelle VARCHAR(100) NOT NULL,
    pays VARCHAR(100) NOT NULL,
    nationalite VARCHAR(100) NOT NULL,
    telephone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    etat_civil VARCHAR(50) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    cover_photo VARCHAR(255) DEFAULT NULL,
    email_verified_at DATETIME DEFAULT NULL,
    phone_verified_at DATETIME DEFAULT NULL,
    last_seen DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison Utilisateurs <-> Domaines (Restaurée)
-- Doit être créée après users et domains
DROP TABLE IF EXISTS user_domains;
CREATE TABLE user_domains (
    user_id INT NOT NULL,
    domain_id INT NOT NULL,
    PRIMARY KEY (user_id, domain_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
);

-- Table des Publications Utilisateurs (Posts - Nouveau système)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved') DEFAULT 'pending',
    title VARCHAR(255) NULL,
    media_type ENUM('image','video','audio','document') NULL,
    domain_id INT NULL,
    sub_domain_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE SET NULL,
    FOREIGN KEY (sub_domain_id) REFERENCES sub_domains(id) ON DELETE SET NULL
);

-- (Les contraintes FK sont désormais intégrées à la création de la table)

-- Table des Likes (Nouveau système - compatible Posts)
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Table des Commentaires (Nouveau système - compatible Posts)
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Table des Avis Visiteurs (Feedback - Nouveau système)
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100) DEFAULT 'Anonyme',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des Visites (Stats - Nouveau système)
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45),
    page_url VARCHAR(255),
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des Amitiés (Invitations - Nouveau système)
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(sender_id, receiver_id)
);

-- Table des messages privés entre utilisateurs (amis acceptés)
CREATE TABLE IF NOT EXISTS user_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    attachment_type ENUM('image','document','audio') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des blocages entre utilisateurs
CREATE TABLE IF NOT EXISTS blocks (
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Mentions utilisateurs dans les news
CREATE TABLE IF NOT EXISTS news_mentions (
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (news_id, user_id),
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT NOT NULL,
    post_id INT DEFAULT NULL,
    type ENUM('like', 'comment', 'follow', 'system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Table de réinitialisation de mot de passe
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(token)
);

CREATE TABLE IF NOT EXISTS verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    type ENUM('email','phone') NOT NULL,
    purpose ENUM('register','reset') NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id, type, purpose),
    UNIQUE(code, type, purpose)
);

CREATE TABLE IF NOT EXISTS trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(150) NOT NULL,
    title VARCHAR(150) NULL,
    bio TEXT NULL,
    competences TEXT NULL,
    diplomes TEXT NULL,
    formations_details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS trainer_subdomains (
    trainer_id INT NOT NULL,
    sub_domain_id INT NOT NULL,
    title VARCHAR(150) NULL,
    PRIMARY KEY (trainer_id, sub_domain_id),
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_domain_id) REFERENCES sub_domains(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS formations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_domain_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    resource_url VARCHAR(255) NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    status ENUM('draft','published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_domain_id) REFERENCES sub_domains(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS formation_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formation_id INT NOT NULL,
    trainer_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    link VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS formation_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    formation_id INT NOT NULL,
    status ENUM('pending','active','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    UNIQUE(user_id, formation_id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    formation_id INT NOT NULL,
    method ENUM('mpesa','airtel','orange','visa') NOT NULL,
    payer_number VARCHAR(50) NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE
);
 
CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    social_bg VARCHAR(255) DEFAULT NULL,
    messages_bg VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_privacy (
    user_id INT NOT NULL,
    field VARCHAR(50) NOT NULL,
    visibility ENUM('public','friends','private') DEFAULT 'public',
    PRIMARY KEY (user_id, field),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform ENUM('whatsapp','facebook','instagram') NOT NULL,
    url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, platform)
);
-- ==========================================
-- 3. DONNÉES INITIALES (RESTAURATION)
-- ==========================================

-- Domaines
INSERT IGNORE INTO domains (id, name, icon, description) VALUES
(1, 'Informatique', 'fas fa-laptop-code', 'Développement, IA, Réseaux, et Tech.'),
(2, 'Sport', 'fas fa-futbol', 'Football, Basketball, et actualités sportives.'),
(3, 'Culture', 'fas fa-theater-masks', 'Traditions, événements et histoire.'),
(4, 'Aide Humanitaire', 'fas fa-hands-helping', 'Solidarité, bénévolat et actions sociales.'),
(5, 'Art', 'fas fa-palette', 'Peinture, sculpture et créativité.'),
(6, 'Éducation', 'fas fa-graduation-cap', 'Apprentissage, formation et savoir.');

-- Sous-Domaines
INSERT IGNORE INTO sub_domains (domain_id, name) VALUES 
(1, 'Programmation'), (1, 'Réseaux'), (1, 'Sécurité'), (1, 'Intelligence Artificielle'),
(2, 'Football'), (2, 'Basketball'), (2, 'Athlétisme'), (2, 'Tennis'),
(3, 'Musique'), (3, 'Danse'), (3, 'Cinéma'), (3, 'Théâtre'),
(4, 'Dons'), (4, 'Bénévolat'), (4, 'Urgence'), (4, 'Développement durable'),
(5, 'Peinture'), (5, 'Sculpture'), (5, 'Photographie'), (5, 'Design'),
(6, 'Scolaire'), (6, 'Formation Pro'), (6, 'Langues'), (6, 'Universitaire');

-- Admin par défaut
INSERT INTO users (name, nom, prenom, date_naissance, lieu_naissance, ville_actuelle, pays, nationalite, telephone, email, password, age, etat_civil, role)
VALUES ('Admin Principal', 'Admin', 'Principal', '1990-01-01', 'Paris', 'Paris', 'France', 'Française', '+243816762928', 'admin@kayoka.com', '$2y$10$rWQDKQlBA78eSewU1JTt.ekNhUw4PXIdefPYIJwvZyrDdaPmUXQyO', 30, 'Célibataire', 'admin')
ON DUPLICATE KEY UPDATE role='admin';

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- 4. TABLES CHAT (NOUVEAU SYSTÈME)
-- ==========================================

-- Table des Groupes de Discussion (Formations)
CREATE TABLE IF NOT EXISTS chat_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formation_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des Membres des Groupes de Discussion
CREATE TABLE IF NOT EXISTS chat_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    can_post TINYINT(1) DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(group_id, user_id)
);

-- Table des Messages des Groupes
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    attachment_type ENUM('image','video','audio','document') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

