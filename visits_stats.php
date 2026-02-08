<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

try {
    $total = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
    $today = $pdo->query("SELECT COUNT(*) FROM visits WHERE DATE(visit_time)=CURDATE()")->fetchColumn();
    $last5 = $pdo->query("SELECT COUNT(*) FROM visits WHERE visit_time > NOW() - INTERVAL 5 MINUTE")->fetchColumn();
    $online = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM visits WHERE visit_time > NOW() - INTERVAL 5 MINUTE")->fetchColumn();
    $stmt = $pdo->query("SELECT page_url, COUNT(*) as c FROM visits WHERE visit_time > NOW() - INTERVAL 1 DAY GROUP BY page_url ORDER BY c DESC LIMIT 10");
    $top = $stmt->fetchAll();
    echo json_encode(['success'=>true,'total'=>$total,'today'=>$today,'last5'=>$last5,'online'=>$online,'top'=>$top]);
} catch (Exception $e) {
    echo json_encode(['success'=>false]);
}
