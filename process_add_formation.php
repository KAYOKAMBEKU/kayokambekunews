<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: formations.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: add_formation.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: add_formation.php'); exit; }
$uid = $_SESSION['user_id'];
$subId = intval($_POST['sub_domain_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$res = trim($_POST['resource_url'] ?? '');
$start = !empty($_POST['start_time']) ? $_POST['start_time'] : date('Y-m-d H:i:s');
$end = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
$status = $_POST['status'] ?? 'published';
$trainerId = intval($_POST['trainer_id'] ?? 0);

// Validation simplifiée : Seuls le sous-domaine, le titre et le formateur sont strictement requis
if ($subId > 0 && $title !== '' && $trainerId > 0) {
    $stmt = $pdo->prepare("INSERT INTO formations (user_id, sub_domain_id, title, description, price, resource_url, start_time, end_time, status, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$uid, $subId, $title, $desc, $price, $res ?: null, $start, $end, $status, $trainerId]);
    $formationId = $pdo->lastInsertId();

    // 1. Création automatique du groupe de discussion
    $groupName = "Groupe - " . $title;
    $stmt = $pdo->prepare("INSERT INTO chat_groups (formation_id, name, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$formationId, $groupName, $uid]);
    $groupId = $pdo->lastInsertId();

    // 2. Ajouter le formateur au groupe (Admin + Peut poster)
    // Récupérer le user_id du formateur
    $stmt = $pdo->prepare("SELECT user_id FROM trainers WHERE id = ?");
    $stmt->execute([$trainerId]);
    $trainerUserId = $stmt->fetchColumn();

    if ($trainerUserId) {
        $stmt = $pdo->prepare("INSERT INTO chat_group_members (group_id, user_id, is_admin, can_post) VALUES (?, ?, 1, 1)");
        $stmt->execute([$groupId, $trainerUserId]);
    }

    // 3. Ajouter l'admin créateur au groupe (optionnel, mais utile pour modération)
    if ($uid != $trainerUserId) {
        $stmt = $pdo->prepare("INSERT INTO chat_group_members (group_id, user_id, is_admin, can_post) VALUES (?, ?, 1, 1)");
        try {
            $stmt->execute([$groupId, $uid]);
        } catch (Exception $e) {}
    }

    // 4. Ajouter tous les utilisateurs intéressés par ce sous-domaine (Lecture seule)
    // On cherche les utilisateurs qui ont ce domaine d'intérêt via user_domains -> sub_domains ?
    // user_domains lie user -> domain. sub_domains lie sub -> domain.
    // Donc si la formation est dans "PHP" (sous-domaine de "Informatique"), on ajoute tous ceux qui ont "Informatique" comme domaine ?
    // Ou on ajoute personne par défaut ? Le prompt dit: "tous les inscrits seront ajouter dans ce groupe de formation selon leurs domaines choisit pendant l'inscription"
    // "Inscrits" peut vouloir dire "Inscrits sur le site".
    // On va chercher le domain_id du sub_domain_id
    $stmt = $pdo->prepare("SELECT domain_id FROM sub_domains WHERE id = ?");
    $stmt->execute([$subId]);
    $domainId = $stmt->fetchColumn();

    if ($domainId) {
        $stmt = $pdo->prepare("SELECT user_id FROM user_domains WHERE domain_id = ?");
        $stmt->execute([$domainId]);
        $interestedUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmtInsert = $pdo->prepare("INSERT IGNORE INTO chat_group_members (group_id, user_id, is_admin, can_post) VALUES (?, ?, 0, 0)");
        $stmtEnrollInsert = $pdo->prepare("INSERT IGNORE INTO formation_enrollments (user_id, formation_id, status) VALUES (?, ?, 'active')");
        
        foreach ($interestedUsers as $iUserId) {
            // Ne pas ajouter si c'est déjà le formateur ou l'admin
            if ($iUserId == $trainerUserId || $iUserId == $uid) continue;
            
            // Ajout au groupe
            $stmtInsert->execute([$groupId, $iUserId]);
            
            // Ajout à l'enrollment (pour visibilité dans Mes Formations)
            $stmtEnrollInsert->execute([$iUserId, $formationId]);
        }
    }
}
header('Location: formations.php');
exit;
