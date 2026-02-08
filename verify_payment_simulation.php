<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
$fid = intval($_GET['fid'] ?? 0);

if ($fid > 0) {
    // 1. Verify if there is a pending payment
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND formation_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$uid, $fid]);
    $payment = $stmt->fetch();

    if ($payment) {
        // 2. Update Payment Status
        $updatePayment = $pdo->prepare("UPDATE payments SET status = 'paid' WHERE id = ?");
        $updatePayment->execute([$payment['id']]);

        // 3. Update Enrollment Status
        $updateEnrollment = $pdo->prepare("UPDATE formation_enrollments SET status = 'active' WHERE user_id = ? AND formation_id = ?");
        $updateEnrollment->execute([$uid, $fid]);

        // 3b. Add/Update User in Chat Group (with can_post=1 for paid students)
        $stmtGroup = $pdo->prepare("SELECT id FROM chat_groups WHERE formation_id = ?");
        $stmtGroup->execute([$fid]);
        $groupId = $stmtGroup->fetchColumn();

        if ($groupId) {
            // Check if already member
            $stmtCheck = $pdo->prepare("SELECT 1 FROM chat_group_members WHERE group_id = ? AND user_id = ?");
            $stmtCheck->execute([$groupId, $uid]);
            if ($stmtCheck->fetchColumn()) {
                // Update permission
                $stmtUpd = $pdo->prepare("UPDATE chat_group_members SET can_post = 1 WHERE group_id = ? AND user_id = ?");
                $stmtUpd->execute([$groupId, $uid]);
            } else {
                // Insert new member
                $stmtIns = $pdo->prepare("INSERT INTO chat_group_members (group_id, user_id, is_admin, can_post) VALUES (?, ?, 0, 1)");
                $stmtIns->execute([$groupId, $uid]);
            }
        }

        // 4. Redirect with success to the Classroom (formation_view.php)
        header("Location: formation_view.php?id=$fid&enrollment=success");
        exit;
    }
}

// If something goes wrong
header("Location: formations.php?error=verification_failed");
exit;
