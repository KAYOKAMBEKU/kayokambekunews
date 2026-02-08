<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
$q = trim($_GET['q'] ?? '');
if ($q === '') { echo json_encode(['success'=>true,'results'=>[]]); exit; }
$stmt = $pdo->prepare("SELECT id, prenom, nom, profile_pic FROM users WHERE CONCAT(prenom,' ',nom) LIKE ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute(['%'.$q.'%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success'=>true,'results'=>$rows]);
