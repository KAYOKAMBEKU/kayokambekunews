<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$userId = $_SESSION['user_id'];
$formationId = intval($_GET['id'] ?? 0);

if ($formationId <= 0) { header('Location: formations.php'); exit; }

// Fetch formation details
$stmt = $pdo->prepare("SELECT f.*, u.nom, u.prenom FROM formations f JOIN users u ON f.trainer_id = u.id WHERE f.id = ?");
$stmt->execute([$formationId]);
$formation = $stmt->fetch();

if (!$formation) { echo "Formation introuvable."; exit; }

// Check access (Trainer, Admin, or Enrolled Student)
$isTrainer = ($formation['trainer_id'] == $userId);
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$isEnrolled = false;

if (!$isTrainer && !$isAdmin) {
    $stmtEnroll = $pdo->prepare("SELECT 1 FROM formation_enrollments WHERE formation_id = ? AND user_id = ? AND status = 'active'");
    $stmtEnroll->execute([$formationId, $userId]);
    if ($stmtEnroll->fetchColumn()) {
        $isEnrolled = true;
    }
}

if (!$isTrainer && !$isAdmin && !$isEnrolled) {
    echo "Accès refusé. Vous n'êtes pas inscrit à cette formation.";
    exit;
}

// Fetch Group ID
$stmtGroup = $pdo->prepare("SELECT id FROM chat_groups WHERE formation_id = ?");
$stmtGroup->execute([$formationId]);
$groupId = $stmtGroup->fetchColumn();

// Fetch Materials
$stmtMat = $pdo->prepare("SELECT * FROM course_materials WHERE formation_id = ? ORDER BY created_at DESC");
$stmtMat->execute([$formationId]);
$allMaterials = $stmtMat->fetchAll();

$library = []; // Docs
$lab = [];     // Videos

foreach ($allMaterials as $m) {
    if ($m['type'] === 'video') {
        $lab[] = $m;
    } else {
        $library[] = $m;
    }
}

// Handle Live Class Action (Start/Stop) - Trainer Only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($isTrainer || $isAdmin)) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'start_live') {
            $roomName = "KayokaLive_" . $formationId . "_" . uniqid();
            $url = "https://meet.jit.si/" . $roomName; // Using Jitsi public server for now
            $stmtUpd = $pdo->prepare("UPDATE formations SET video_meeting_url = ? WHERE id = ?");
            $stmtUpd->execute([$url, $formationId]);
            $formation['video_meeting_url'] = $url; // Update local var
        } elseif ($_POST['action'] === 'stop_live') {
            $stmtUpd = $pdo->prepare("UPDATE formations SET video_meeting_url = NULL WHERE id = ?");
            $stmtUpd->execute([$formationId]);
            $formation['video_meeting_url'] = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($formation['title']); ?> - Espace de Formation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .material-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px; border-bottom: 1px solid #eee;
        }
        .material-item:last-child { border-bottom: none; }
        .upload-box {
            border: 2px dashed #ccc; padding: 20px; text-align: center; margin-bottom: 20px;
            background: #f9f9f9; border-radius: 8px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container-narrow section">
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h1><?php echo htmlspecialchars($formation['title']); ?></h1>
                <p class="text-muted">Formateur: <?php echo htmlspecialchars($formation['prenom'] . ' ' . $formation['nom']); ?></p>
            </div>
            <?php if ($groupId): ?>
                <a href="messages.php?group_id=<?php echo $groupId; ?>" class="btn-outline" style="text-decoration:none;">
                    <i class="fas fa-comments"></i> Discussion
                </a>
            <?php endif; ?>
        </div>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="tab-biblio">Bibliothèque</button>
            <button class="tab-btn" data-tab="tab-labo">Laboratoire</button>
            <button class="tab-btn" data-tab="tab-live">Classe Virtuelle</button>
        </div>

        <!-- Bibliothèque (Docs) -->
        <div id="tab-biblio" class="tab-content active">
            <h3>Documents de cours (PDF, Word)</h3>
            
            <?php if ($isTrainer || $isAdmin): ?>
            <div class="upload-box">
                <h4>Ajouter un document</h4>
                <form action="upload_course_material.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="formation_id" value="<?php echo $formationId; ?>">
                    <input type="hidden" name="type" value="document">
                    <input type="text" name="title" placeholder="Titre du document" required style="margin-bottom:10px; width:100%; padding:8px;">
                    <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx" required style="margin-bottom:10px;">
                    <button class="btn-submit">Uploader</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="material-list">
                <?php if (empty($library)): ?>
                    <p class="text-muted">Aucun document disponible.</p>
                <?php else: ?>
                    <?php foreach ($library as $item): ?>
                        <div class="material-item">
                            <div>
                                <i class="fas fa-file-alt"></i> <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                <span class="text-muted" style="font-size:0.8rem;">(<?php echo date('d/m/Y', strtotime($item['created_at'])); ?>)</span>
                            </div>
                            <a href="<?php echo htmlspecialchars($item['file_path']); ?>" target="_blank" class="btn-outline" style="padding:5px 10px; font-size:0.9rem;">Télécharger</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Laboratoire (Vidéos) -->
        <div id="tab-labo" class="tab-content">
            <h3>Laboratoire Vidéo</h3>

            <?php if ($isTrainer || $isAdmin): ?>
            <div class="upload-box">
                <h4>Ajouter une vidéo</h4>
                <form action="upload_course_material.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="formation_id" value="<?php echo $formationId; ?>">
                    <input type="hidden" name="type" value="video">
                    <input type="text" name="title" placeholder="Titre de la vidéo" required style="margin-bottom:10px; width:100%; padding:8px;">
                    <input type="file" name="file" accept="video/*" required style="margin-bottom:10px;">
                    <button class="btn-submit">Uploader</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="material-list">
                <?php if (empty($lab)): ?>
                    <p class="text-muted">Aucune vidéo disponible.</p>
                <?php else: ?>
                    <?php foreach ($lab as $item): ?>
                        <div class="material-item" style="flex-direction:column; align-items:flex-start;">
                            <div style="margin-bottom:10px;">
                                <i class="fas fa-video"></i> <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            </div>
                            <video controls style="width:100%; max-height:400px; background:#000; border-radius:8px;">
                                <source src="<?php echo htmlspecialchars($item['file_path']); ?>" type="video/mp4">
                                Votre navigateur ne supporte pas la lecture vidéo.
                            </video>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Classe Virtuelle -->
        <div id="tab-live" class="tab-content">
            <div style="text-align:center; padding:40px;">
                <i class="fas fa-video" style="font-size:3rem; color:var(--brand); margin-bottom:20px;"></i>
                <h3>Cours en Direct</h3>
                
                <?php if ($formation['video_meeting_url']): ?>
                    <div style="background:#d4edda; color:#155724; padding:20px; border-radius:8px; margin-bottom:20px;">
                        <strong>Un cours est actuellement en cours !</strong>
                    </div>
                    <a href="<?php echo htmlspecialchars($formation['video_meeting_url']); ?>" target="_blank" class="btn-submit" style="font-size:1.2rem; padding:15px 30px;">Rejoindre la réunion</a>
                    
                    <?php if ($isTrainer || $isAdmin): ?>
                        <form method="POST" style="margin-top:20px;">
                            <input type="hidden" name="action" value="stop_live">
                            <button class="btn-outline" style="color:red; border-color:red;">Terminer le cours</button>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Aucun cours en direct n'est actif pour le moment.</p>
                    
                    <?php if ($isTrainer || $isAdmin): ?>
                        <form method="POST" style="margin-top:20px;">
                            <input type="hidden" name="action" value="start_live">
                            <button class="btn-submit">Lancer un cours vidéo maintenant</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
        btn.classList.add('active');
        const id = btn.getAttribute('data-tab');
        document.getElementById(id).classList.add('active');
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
