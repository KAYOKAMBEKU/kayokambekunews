<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Récupérer les domaines pour le formulaire
$stmt = $pdo->query("SELECT * FROM domains ORDER BY name");
$domains = $stmt->fetchAll();
$stmt = $pdo->query("SELECT * FROM sub_domains ORDER BY name");
$allSubs = $stmt->fetchAll();

// Traitement : Créer une publication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!csrf_verify($csrf)) {
        $error_msg = "Session expirée, veuillez réessayer.";
    } else {
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $mediaType = isset($_POST['media_type']) ? $_POST['media_type'] : 'image';
        $allowedTypes = ['image','video','audio','document'];
        if (!in_array($mediaType, $allowedTypes, true)) { $mediaType = 'image'; }
    $domainIdRaw = isset($_POST['domain_id']) ? $_POST['domain_id'] : null;
    $domainId = null;
    if ($domainIdRaw !== null && $domainIdRaw !== '') {
        $tmp = intval($domainIdRaw);
        if ($tmp > 0) { $domainId = $tmp; }
    }
    $mediaPath = null;
    $subDomainIdRaw = isset($_POST['sub_domain_id']) ? $_POST['sub_domain_id'] : null;
    $subDomainId = null;
    if ($subDomainIdRaw !== null && $subDomainIdRaw !== '') {
        $tmp2 = intval($subDomainIdRaw);
        if ($tmp2 > 0) { $subDomainId = $tmp2; }
    }
    // Upload Media (effectué avant la validation pour autoriser les posts avec uniquement un média)
    if (isset($_FILES['post_media']) && isset($_FILES['post_media']['error']) && $_FILES['post_media']['error'] === 0) {
            $filename = $_FILES['post_media']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $size = isset($_FILES['post_media']['size']) ? $_FILES['post_media']['size'] : 0;
            $tmp = $_FILES['post_media']['tmp_name'];
            
            // Définir les extensions autorisées selon le type
            $allowed = [];
            if ($mediaType === 'image') $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            elseif ($mediaType === 'video') $allowed = ['mp4', 'avi', 'mov', 'webm'];
            elseif ($mediaType === 'audio') $allowed = ['mp3', 'wav', 'ogg'];
            elseif ($mediaType === 'document') $allowed = ['pdf', 'doc', 'docx', 'txt'];

            // Taille max 10MB
            $maxSize = 10 * 1024 * 1024;
            // Vérification MIME
            $mimeOk = true;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if ($mediaType === 'image') $mimeOk = in_array($mime, ['image/jpeg','image/png','image/gif'], true);
                elseif ($mediaType === 'video') $mimeOk = in_array($mime, ['video/mp4','video/webm','video/quicktime','video/x-msvideo'], true);
                elseif ($mediaType === 'audio') $mimeOk = in_array($mime, ['audio/mpeg','audio/wav','audio/ogg'], true);
                elseif ($mediaType === 'document') $mimeOk = in_array($mime, ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','text/plain'], true);
            }

            if (in_array($ext, $allowed) && $size > 0 && $size <= $maxSize && $mimeOk) {
                $newFilename = 'post_' . uniqid() . '.' . $ext;
                $targetDir = 'uploads/posts/';
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['post_media']['tmp_name'], $targetDir . $newFilename)) {
                    $mediaPath = $targetDir . $newFilename;
                }
            } else {
                $error_msg = "Fichier invalide (type/MIME/taille).";
            }
    }

    // Valider qu'il y a bien un contenu ou un média
    $hasSomething = ($mediaPath !== null) || ($title !== '') || ($content !== '');
    if (!$hasSomething) {
        $error_msg = "Ajoutez un texte (titre/contenu) ou un média.";
    } else {
        // Domain obligatoire
        if ($domainId === null) {
            $error_msg = "Choisissez le domaine de votre publication.";
        }
        // Détection insultes/profanités
        $badWords = ['merde','putain','salope','connard','con','idiot','imbécile','fuck','shit','bitch','fdp'];
        $text = mb_strtolower($title.' '.$content);
        foreach ($badWords as $bw) {
            if (preg_match('/\b'.preg_quote($bw,'/').'\b/u', $text)) {
                $error_msg = "Votre publication contient des propos inappropriés. Veuillez les retirer.";
                break;
            }
        }
        // Avertissement si le contenu ne semble pas cadrer avec le domaine
        $warning_msg = null;
        if ($domainId !== null) {
            // Charger le nom de domaine et sous-domaines pour heuristique
            $stmtD = $pdo->prepare("SELECT name FROM domains WHERE id=?"); $stmtD->execute([$domainId]);
            $dname = mb_strtolower($stmtD->fetchColumn() ?: '');
            $stmtSD = $pdo->prepare("SELECT name FROM sub_domains WHERE domain_id=?"); $stmtSD->execute([$domainId]);
            $kwMatch = false;
            if ($dname && preg_match('/\b'.preg_quote($dname,'/').'\b/u', $text)) $kwMatch = true;
            while($row=$stmtSD->fetch(PDO::FETCH_ASSOC)){
                $nm = mb_strtolower($row['name']);
                if ($nm && preg_match('/\b'.preg_quote($nm,'/').'\b/u', $text)) { $kwMatch = true; break; }
            }
            if (!$kwMatch && empty($error_msg)) {
                $warning_msg = "Avertissement: votre contenu ne semble pas correspondre au domaine sélectionné.";
            }
        }
        // Insérer le post
        $status = ($user['role'] === 'admin') ? 'approved' : 'pending';
        // Utiliser la colonne 'image' pour stocker le chemin du média (compatible avec l'ancien schéma)
        // Ou utiliser media_url si on l'a ajouté, mais on a décidé d'utiliser 'image' ou 'media_url'.
        // Le schéma actuel a 'image' VARCHAR(255).
        // SQL update a ajouté media_type et domain_id.
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, status, title, media_type, domain_id, sub_domain_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $content, $mediaPath, $status, $title, $mediaType, $domainId, $subDomainId]);
        } catch (Exception $e) {
            $error_msg = "Impossible d'enregistrer la publication. Vérifiez le domaine sélectionné.";
        }
        
        if (empty($error_msg)) {
            if ($status === 'approved') {
                $success_msg = "Votre publication a été publiée.";
            } else {
                $success_msg = "Votre publication a été envoyée et est en attente d'approbation.";
            }
            if ($warning_msg) {
                $success_msg .= " " . $warning_msg;
            }
        }
    }
    }
}

// Récupérer les posts de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$myPosts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - KAYOKA MBEKU NEWS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: #fff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            position: relative;
            /* Bordure supérieure dégradée */
            border-top: 8px solid transparent;
            background-image: linear-gradient(#fff, #fff), linear-gradient(90deg, #0f766e, #3b82f6);
            background-origin: border-box;
            background-clip: padding-box, border-box;
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid transparent;
            background: linear-gradient(white, white) padding-box, linear-gradient(90deg, #0f766e, #3b82f6) border-box;
        }
        .profile-header h2 {
            font-weight: 800;
            background: linear-gradient(90deg, #0f766e, #3b82f6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .post-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        .post-card h3 {
            font-weight: 700;
            color: var(--brand);
            margin-bottom: 1rem;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending { background: #ffeeba; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        
        /* Modern Tabs - Clean Style */
        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0;
        }
        .tab-btn {
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: #64748b;
            padding: 10px 5px;
            border-radius: 0;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
            margin-bottom: -2px; /* Overlap border */
        }
        .tab-btn:hover {
            color: var(--brand);
            background: transparent;
            transform: none;
        }
        .tab-btn.active {
            border-bottom-color: var(--brand);
            color: var(--brand);
            background: transparent;
            box-shadow: none;
        }
        
        .btn-submit {
            background: linear-gradient(90deg, #0f766e, #3b82f6);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="padding: 2rem 0;">
        <!-- Profil -->
        <div class="profile-header">
            <img src="<?php echo $user['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/100' : 'uploads/profiles/'.$user['profile_pic']; ?>" alt="Profil" class="profile-pic">
            <div>
                <h2>Bonjour, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                <p>Ville: <?php echo htmlspecialchars($user['ville_actuelle']); ?></p>
            </div>
        </div>

        <div class="tabs" style="margin-bottom:1rem;">
            <button class="tab-btn active" data-tab="tab-feed">Fil & Publications</button>
            <button class="tab-btn" data-tab="tab-messages">Messages & Invitations</button>
            <button class="tab-btn" data-tab="tab-privacy">Confidentialité</button>
            <button class="tab-btn" data-tab="tab-settings">Paramètres</button>
            <button class="tab-btn" data-tab="tab-formations">Formations</button>
            <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <button class="tab-btn" data-tab="tab-admin">Administration</button>
            <?php endif; ?>
        </div>

        <div id="tab-feed" class="tab-content active">
        <div class="grid-2">
            <!-- Colonne Principale : Créer Post + Liste Posts -->
            <div>
                <div class="post-card">
                    <h3>Exprimez-vous</h3>
                    <?php if(isset($success_msg)) echo "<p style='color:green'>$success_msg</p>"; ?>
                    <?php if(isset($error_msg)) echo "<p style='color:red'>$error_msg</p>"; ?>
                    
                    <form action="" method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="text" name="title" placeholder="Titre (Optionnel, pour Articles/Livres)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;">
                        
                        <textarea name="content" placeholder="Quoi de neuf ?" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        
                        <div class="grid-2" style="margin-top: 10px;">
                            <select name="media_type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="image">Image</option>
                                <option value="video">Vidéo</option>
                                <option value="audio">Audio</option>
                                <option value="document">Livre / Document (PDF)</option>
                            </select>
                            <select name="domain_id" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Choisir un domaine...</option>
                                <?php foreach ($domains as $domain): ?>
                                    <option value="<?php echo $domain['id']; ?>"><?php echo htmlspecialchars($domain['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-top:10px;">
                            <select name="sub_domain_id" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="">Sous-domaine (optionnel)</option>
                                <?php foreach ($allSubs as $sd): ?>
                                    <option value="<?php echo $sd['id']; ?>"><?php echo htmlspecialchars($sd['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <input type="file" name="post_media" id="post_media" accept="image/*,video/*,audio/*,application/pdf">
                            <button type="submit" name="create_post" class="btn-submit" style="width: auto; padding: 0.5rem 1.5rem;">Publier</button>
                        </div>
                        <div id="media_preview" style="margin-top:10px;"></div>
                    </form>
                </div>

                <h3>Vos publications</h3>
                <?php if (empty($myPosts)): ?>
                    <p>Vous n'avez rien publié pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($myPosts as $post): ?>
                        <div class="post-card">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <small><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></small>
                                <span class="status-badge <?php echo 'status-' . $post['status']; ?>">
                                    <?php echo $post['status'] == 'approved' ? 'Approuvé' : 'En attente'; ?>
                                </span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php if ($post['image']): ?>
                                <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="responsive-img" style="margin-top: 10px; border-radius: 4px;">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div>
                <div class="post-card">
                    <h3>Mes Infos</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li><strong>Nationalité:</strong> <?php echo htmlspecialchars($user['nationalite']); ?></li>
                        <li><strong>Age:</strong> <?php echo htmlspecialchars($user['age']); ?> ans</li>
                        <li><strong>Etat Civil:</strong> <?php echo htmlspecialchars($user['etat_civil']); ?></li>
                        <li><strong>Téléphone:</strong> <?php echo htmlspecialchars($user['telephone']); ?></li>
                    </ul>
                    <a href="edit_profile.php" class="btn-submit" style="display: block; text-align: center; margin-top: 1rem; text-decoration: none;">Modifier mes informations</a>
                    <a href="change_password.php" class="btn-submit" style="display: block; text-align: center; margin-top: 0.5rem; text-decoration: none; background: var(--accent-blue);">Changer mon mot de passe</a>
                </div>
            </div>
        </div>
        </div>

        <div id="tab-messages" class="tab-content">
            <div class="grid-2">
                <!-- Invitations -->
                <div class="post-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h3 style="margin:0;"><i class="fas fa-user-plus"></i> Invitations</h3>
                        <span style="background:var(--accent); padding:2px 8px; border-radius:10px; font-size:0.8rem; font-weight:bold;"><?php echo isset($invites) ? count($invites) : 0; ?></span>
                    </div>
                    <?php
                        // Re-fetch invites if needed or ensure variable scope
                        if (!isset($invites)) {
                            $stmt = $pdo->prepare("SELECT f.sender_id, u.prenom, u.nom FROM friendships f JOIN users u ON u.id = f.sender_id WHERE f.receiver_id = ? AND f.status = 'pending'");
                            $stmt->execute([$userId]);
                            $invites = $stmt->fetchAll();
                        }
                    ?>
                    <?php if (empty($invites)): ?>
                        <div style="text-align:center; padding:20px; color:#888;">
                            <i class="fas fa-check-circle" style="font-size:2rem; color:#ddd; margin-bottom:10px;"></i>
                            <p>Aucune nouvelle invitation.</p>
                        </div>
                    <?php else: foreach ($invites as $iv): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:40px; height:40px; background:#eee; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#888;"><i class="fas fa-user"></i></div>
                                <strong><a href="user_profile.php?id=<?php echo $iv['sender_id']; ?>" style="text-decoration:none; color:inherit;"><?php echo htmlspecialchars($iv['prenom'].' '.$iv['nom']); ?></a></strong>
                            </div>
                            <form action="respond_invite.php" method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="sender_id" value="<?php echo $iv['sender_id']; ?>">
                                <button type="submit" name="action" value="accept" style="background:none; border:none; color:green; cursor:pointer; font-size:1.2rem;" title="Accepter"><i class="fas fa-check-circle"></i></button>
                                <button type="submit" name="action" value="reject" style="background:none; border:none; color:red; cursor:pointer; font-size:1.2rem;" title="Refuser"><i class="fas fa-times-circle"></i></button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Messages -->
                <div class="post-card">
                    <h3 style="margin-bottom:15px;"><i class="fas fa-comments"></i> Messagerie</h3>
                    <div style="background:#f8fafc; border-radius:12px; padding:20px; text-align:center;">
                        <i class="fas fa-paper-plane" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px;"></i>
                        <p style="color:#64748b; margin-bottom:20px;">Discutez en privé avec vos amis et partagez des moments.</p>
                        <a href="messages.php" class="btn-submit" style="display:inline-block; text-decoration:none; padding:10px 30px;">
                            Accéder à mes messages
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-privacy" class="tab-content">
            <?php
            $fields = ['email','telephone','ville_actuelle','pays','age','etat_civil'];
            $vis = [];
            $st = $pdo->prepare("SELECT field, visibility FROM user_privacy WHERE user_id=?");
            $st->execute([$userId]);
            foreach ($st->fetchAll() as $row) { $vis[$row['field']]=$row['visibility']; }
            function sel($cur,$val){ return ($cur===$val)?'selected':''; }
            ?>
            <div class="post-card">
                <h3>Confidentialité</h3>
                <form action="privacy_settings.php" method="POST" style="display:grid; gap:10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <?php foreach ($fields as $f): $cur=$vis[$f]??'public'; ?>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <label style="width:180px;"><?php echo htmlspecialchars($f); ?></label>
                            <select name="vis_<?php echo htmlspecialchars($f); ?>">
                                <option value="public" <?php echo sel($cur,'public'); ?>>Public</option>
                                <option value="friends" <?php echo sel($cur,'friends'); ?>>Amis</option>
                                <option value="private" <?php echo sel($cur,'private'); ?>>Privé</option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn-submit" style="width:auto;">Enregistrer</button>
                </form>
            </div>
        </div>

        <div id="tab-settings" class="tab-content">
            <?php
            $st2 = $pdo->prepare("SELECT social_bg, messages_bg FROM user_settings WHERE user_id=?");
            $st2->execute([$userId]);
            $curSet = $st2->fetch(PDO::FETCH_ASSOC) ?: ['social_bg'=>null,'messages_bg'=>null];
            ?>
            <div class="post-card">
                <h3>Paramètres d'affichage</h3>
                <form action="user_settings.php" method="POST" style="display:grid; gap:10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <label>Arrière-plan du Social (URL)</label>
                    <input type="url" name="social_bg" value="<?php echo htmlspecialchars($curSet['social_bg'] ?? ''); ?>" placeholder="https://...">
                    <label>Arrière-plan des Messages (URL)</label>
                    <input type="url" name="messages_bg" value="<?php echo htmlspecialchars($curSet['messages_bg'] ?? ''); ?>" placeholder="https://...">
                    <button class="btn-submit" style="width:auto;">Enregistrer</button>
                </form>
            </div>
        </div>

        <div id="tab-formations" class="tab-content">
            <div class="post-card">
                <h3>Formations</h3>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role']==='admin'): ?>
                    <a class="btn-outline" style="text-decoration:none; width:auto;" href="add_formation.php">Ajouter une formation</a>
                    <?php endif; ?>
                    <a class="btn-outline" style="text-decoration:none; width:auto;" href="formations.php">Voir les formations</a>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <div id="tab-admin" class="tab-content">
            <div class="post-card">
                <h3>Administration</h3>
                <a class="btn-submit" style="width:auto; text-decoration:none;" href="admin_dashboard.php">Ouvrir le tableau de bord</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <script>
    document.querySelectorAll('.tab-btn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
            btn.classList.add('active');
            const id = btn.getAttribute('data-tab');
            const el = document.getElementById(id);
            if (el) el.classList.add('active');
        });
    });
    (function(){
        var input = document.getElementById('post_media');
        var preview = document.getElementById('media_preview');
        if (!input || !preview) return;
        input.addEventListener('change', function(){
            preview.innerHTML = '';
            var file = this.files && this.files[0];
            if (!file) return;
            var type = file.type;
            var url = URL.createObjectURL(file);
            if (type.indexOf('image/') === 0) {
                var img = document.createElement('img'); img.src = url; img.style.maxWidth='100%'; img.style.borderRadius='8px';
                preview.appendChild(img);
            } else if (type.indexOf('video/') === 0) {
                var v = document.createElement('video'); v.src = url; v.controls = true; v.style.width='100%'; v.style.borderRadius='8px';
                preview.appendChild(v);
            } else if (type.indexOf('audio/') === 0) {
                var a = document.createElement('audio'); a.src = url; a.controls = true; a.style.width='100%';
                preview.appendChild(a);
            } else {
                var div = document.createElement('div'); div.textContent = 'Fichier sélectionné: ' + file.name;
                preview.appendChild(div);
            }
        });
    })();
    </script>
    <?php include 'footer.php'; ?>
