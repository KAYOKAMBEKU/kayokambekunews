<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';
$viewer = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$id = intval($_GET['id'] ?? 0);
if ($id<=0) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { header('Location: index.php'); exit; }
$isFriend = false;
if ($viewer && $viewer != $id) {
    $st = $pdo->prepare("SELECT 1 FROM friendships WHERE status='accepted' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) LIMIT 1");
    $st->execute([$viewer, $id, $id, $viewer]);
    $isFriend = (bool)$st->fetchColumn();
}
$privacy = [];
$st = $pdo->prepare("SELECT field, visibility FROM user_privacy WHERE user_id=?");
$st->execute([$id]);
foreach ($st->fetchAll() as $row) { $privacy[$row['field']] = $row['visibility']; }
$settings = ['social_bg'=>null,'messages_bg'=>null];
if ($viewer && $viewer == $id) {
    $st2 = $pdo->prepare("SELECT social_bg, messages_bg FROM user_settings WHERE user_id=?");
    $st2->execute([$viewer]);
    $rowSet = $st2->fetch(PDO::FETCH_ASSOC);
    if ($rowSet) { $settings = $rowSet; }
}

// Récupération des photos (Album)
$photoSql = "SELECT * FROM posts WHERE user_id = ? AND media_type = 'image' AND image IS NOT NULL";
$params = [$id];

if ($viewer != $id) {
    if ($isFriend) {
        $photoSql .= " AND privacy IN ('public', 'friends')";
    } else {
        $photoSql .= " AND privacy = 'public'";
    }
}
$photoSql .= " ORDER BY created_at DESC";
$stmtPhotos = $pdo->prepare($photoSql);
$stmtPhotos->execute($params);
$photos = $stmtPhotos->fetchAll();

function canShow($field, $privacy, $isFriend) {
    $v = $privacy[$field] ?? 'public';
    if ($v === 'public') return true;
    if ($v === 'friends') return $isFriend;
    return false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container-narrow section">
    <div class="card" style="padding: 0; border: none; overflow: visible;">
        <div class="profile-header-container">
            <?php if (!empty($u['cover_photo'])): ?>
                <img src="uploads/covers/<?php echo htmlspecialchars($u['cover_photo']); ?>" class="profile-cover" alt="Couverture" onerror="this.style.display='none'; document.getElementById('default-cover').style.display='block';">
                <div id="default-cover" class="profile-cover" style="display:none; background: linear-gradient(120deg, var(--brand), var(--accent2));"></div>
            <?php else: ?>
                <div class="profile-cover" style="background: linear-gradient(120deg, var(--brand), var(--accent2));"></div>
            <?php endif; ?>
            
            <div class="profile-avatar-container">
                <img src="<?php echo $u['profile_pic']=='default.png'?'https://via.placeholder.com/150':'uploads/profiles/'.$u['profile_pic']; ?>" class="avatar-profile" alt="Avatar" onerror="this.src='https://via.placeholder.com/150';">
            </div>
        </div>
        
        <div style="padding: 1rem 1.5rem; margin-top: 60px; text-align: left;">
            <div style="display:flex; flex-direction:column; align-items:flex-start; gap:10px;">
                <div>
                    <h1 style="font-size: 2rem; margin-bottom: 5px;"><?php echo htmlspecialchars($u['prenom'].' '.$u['nom']); ?></h1>
                    <?php if ($u['ville_actuelle']): ?>
                        <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($u['ville_actuelle']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($viewer && $viewer != $u['id']): ?>
                    <div style="display:flex; gap:10px;">
                        <?php if ($isFriend): ?>
                            <a href="messages.php?friend_id=<?php echo $u['id']; ?>" class="btn-submit" style="width:auto;"><i class="fas fa-comment"></i> Message</a>
                            <button class="btn-outline" disabled><i class="fas fa-check"></i> Amis</button>
                        <?php else: ?>
                            <!-- Logique d'ajout d'ami à implémenter si besoin, ou lien vers social.php -->
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="padding: 0 1.5rem 1.5rem 1.5rem;">
            <div class="tabs">
                <button class="tab-btn active" data-tab="tab-infos">Infos</button>
                <button class="tab-btn" data-tab="tab-photos">Photos</button>
                <?php if ($viewer && $viewer == $u['id']): ?>
                <button class="tab-btn" data-tab="tab-conf">Confidentialité</button>
                <button class="tab-btn" data-tab="tab-params">Paramètres</button>
                <button class="tab-btn" data-tab="tab-forms">Formations</button>
                <button class="tab-btn" data-tab="tab-sec">Sécurité</button>
                <?php endif; ?>
            </div>

            <!-- Onglet Infos -->
            <div id="tab-infos" class="tab-content active">
                <div class="stack" style="margin-top:10px;">
                    <?php if (canShow('email',$privacy,$isFriend)): ?><div><strong>Email:</strong> <?php echo htmlspecialchars($u['email']); ?></div><?php endif; ?>
                    <?php if (canShow('telephone',$privacy,$isFriend)): ?><div><strong>Téléphone:</strong> <?php echo htmlspecialchars($u['telephone']); ?></div><?php endif; ?>
                    <?php if (canShow('ville_actuelle',$privacy,$isFriend)): ?><div><strong>Ville:</strong> <?php echo htmlspecialchars($u['ville_actuelle']); ?></div><?php endif; ?>
                    <?php if (canShow('pays',$privacy,$isFriend)): ?><div><strong>Pays:</strong> <?php echo htmlspecialchars($u['pays']); ?></div><?php endif; ?>
                    <?php if (canShow('age',$privacy,$isFriend)): ?><div><strong>Âge:</strong> <?php echo htmlspecialchars($u['age']); ?></div><?php endif; ?>
                    <?php if (canShow('etat_civil',$privacy,$isFriend)): ?><div><strong>État Civil:</strong> <?php echo htmlspecialchars($u['etat_civil']); ?></div><?php endif; ?>
                </div>
            </div>

            <!-- Onglet Photos -->
            <div id="tab-photos" class="tab-content">
                <?php if (empty($photos)): ?>
                    <p style="text-align:center; color:#666; padding:20px;">Aucune photo à afficher.</p>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:10px;">
                        <?php foreach($photos as $p): ?>
                            <div style="aspect-ratio:1/1; overflow:hidden; border-radius:8px; cursor:pointer; border:1px solid #ddd;">
                                <a href="uploads/<?php echo htmlspecialchars($p['image']); ?>" target="_blank">
                                    <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" style="width:100%; height:100%; object-fit:cover;" alt="Photo">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($viewer && $viewer == $u['id']): ?>
            <div id="tab-conf" class="tab-content">
            <form action="privacy_settings.php" method="POST" style="display:grid; gap:10px;">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <?php foreach (['email','telephone','ville_actuelle','pays','age','etat_civil'] as $f): $cur=$privacy[$f]??'public'; ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <label style="width:180px;"><?php echo htmlspecialchars($f); ?></label>
                    <select name="vis_<?php echo htmlspecialchars($f); ?>">
                        <option value="public" <?php echo $cur==='public'?'selected':''; ?>>Public</option>
                        <option value="friends" <?php echo $cur==='friends'?'selected':''; ?>>Amis</option>
                        <option value="private" <?php echo $cur==='private'?'selected':''; ?>>Privé</option>
                    </select>
                </div>
                <?php endforeach; ?>
                <button class="btn-submit" style="width:auto;">Enregistrer</button>
            </form>
        </div>
        <div id="tab-params" class="tab-content">
            <form action="user_settings.php" method="POST" style="display:grid; gap:10px;">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <label>Arrière-plan du Social (URL)</label>
                <input type="url" name="social_bg" value="<?php echo htmlspecialchars($settings['social_bg'] ?? ''); ?>" placeholder="https://...">
                <label>Arrière-plan des Messages (URL)</label>
                <input type="url" name="messages_bg" value="<?php echo htmlspecialchars($settings['messages_bg'] ?? ''); ?>" placeholder="https://...">
                <button class="btn-submit" style="width:auto;">Enregistrer</button>
            </form>
        </div>
        <div id="tab-forms" class="tab-content">
            <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role']==='admin'): ?>
            <a class="btn-outline" style="text-decoration:none; width:auto;" href="add_formation.php">Ajouter une formation</a>
            <?php endif; ?>
            <a class="btn-outline" style="text-decoration:none; width:auto; margin-left:8px;" href="formations.php">Voir les formations</a>
        </div>
        <div id="tab-sec" class="tab-content">
            <a class="btn-outline" style="text-decoration:none; width:auto;" href="change_password.php">Changer mon mot de passe</a>
            <a class="btn-outline" style="text-decoration:none; width:auto; margin-left:8px;" href="logout.php">Se déconnecter</a>
        </div>
        <?php endif; ?>
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
        const el = document.getElementById(id);
        if (el) el.classList.add('active');
    });
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
