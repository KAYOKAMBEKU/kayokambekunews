<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: formations.php'); exit; }
if (!csrf_verify($_POST['csrf_token'] ?? '')) { header('Location: formations.php'); exit; }
$uid = $_SESSION['user_id'];
$fid = intval($_POST['formation_id'] ?? 0);
$start = $_POST['start_time'] ?? '';
$link = trim($_POST['link'] ?? '');
if ($fid>0 && $start !== '' && $link !== '') {
    $q = $pdo->prepare("SELECT ts.trainer_id FROM trainer_subdomains ts JOIN formations f ON f.sub_domain_id=ts.sub_domain_id JOIN trainers t ON t.id=ts.trainer_id WHERE t.user_id=? AND f.id=? LIMIT 1");
    $q->execute([$uid, $fid]);
    $tid = $q->fetchColumn();
    if ($tid) {
        $stmt = $pdo->prepare("INSERT INTO formation_sessions (formation_id, trainer_id, start_time, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fid, $tid, $start, $link]);
    }
}
header('Location: view_formation.php?id='.$fid);
exit;
