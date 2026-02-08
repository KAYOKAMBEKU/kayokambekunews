<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/helpers/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur introuvable.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - KAYOKA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="padding: 2rem 0;">
        <h2 class="section-title">Modifier mes informations</h2>
        
        <div class="card form-container">
            <form action="process_edit_profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                
                <div style="text-align: center; margin-bottom: 2rem;">
                    <img src="<?php echo $user['profile_pic'] == 'default.png' ? 'https://via.placeholder.com/150' : 'uploads/profiles/'.$user['profile_pic']; ?>" alt="Profil" class="avatar-lg">
                    <div style="margin-top: 10px;">
                        <label for="profile_pic" style="cursor: pointer; color: var(--primary-green); font-weight: bold;">
                            <i class="fas fa-camera"></i> Changer la photo
                        </label>
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
                    </div>
                    <div style="margin-top:10px;">
                        <label for="cover_photo" style="cursor:pointer; color: var(--brand); font-weight: 600;">
                            <i class="fas fa-image"></i> Ajouter photo de couverture
                        </label>
                        <input type="file" id="cover_photo" name="cover_photo" accept="image/*" style="display:none;">
                    </div>
                    <div style="margin-top:10px; display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                        <div>
                            <label>Zoom (avatar)</label>
                            <input type="range" min="1" max="3" step="0.1" id="avatarZoom" name="avatar_zoom" value="1">
                        </div>
                        <div>
                            <label>Position (X/Y)</label>
                            <input type="number" id="avatarX" name="avatar_x" value="0" style="width:80px;"> 
                            <input type="number" id="avatarY" name="avatar_y" value="0" style="width:80px;">
                        </div>
                    </div>
                    <div id="avatarPreview" style="margin-top:10px; width:200px; height:200px; border-radius:50%; overflow:hidden; margin-left:auto; margin-right:auto; background:#eee;"></div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Ville Actuelle</label>
                        <input type="text" name="ville_actuelle" value="<?php echo htmlspecialchars($user['ville_actuelle']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pays</label>
                        <input type="text" name="pays" value="<?php echo htmlspecialchars($user['pays']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Âge</label>
                        <input type="number" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>État Civil</label>
                        <select name="etat_civil" required>
                            <option value="Célibataire" <?php if($user['etat_civil'] == 'Célibataire') echo 'selected'; ?>>Célibataire</option>
                            <option value="Marié(e)" <?php if($user['etat_civil'] == 'Marié(e)') echo 'selected'; ?>>Marié(e)</option>
                            <option value="Divorcé(e)" <?php if($user['etat_civil'] == 'Divorcé(e)') echo 'selected'; ?>>Divorcé(e)</option>
                            <option value="Veuf/Veuve" <?php if($user['etat_civil'] == 'Veuf/Veuve') echo 'selected'; ?>>Veuf/Veuve</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
        <div class="card" style="max-width: 600px; margin: 1rem auto;">
            <h3>Sécurité</h3>
            <p>Protégez votre compte en mettant à jour votre mot de passe régulièrement.</p>
            <a href="change_password.php" class="btn-submit" style="width: auto; text-decoration: none;">Changer mon mot de passe</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script>
    const avatarInput = document.getElementById('profile_pic');
    const avatarPreview = document.getElementById('avatarPreview');
    const zoom = document.getElementById('avatarZoom');
    const ax = document.getElementById('avatarX');
    const ay = document.getElementById('avatarY');
    let imgEl=null;
    avatarInput?.addEventListener('change', function(){
        const f = this.files && this.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        avatarPreview.innerHTML='';
        imgEl = document.createElement('img');
        imgEl.src = url; imgEl.style.position='relative'; imgEl.style.transformOrigin='top left';
        avatarPreview.appendChild(imgEl);
        applyTransform();
    });
    function applyTransform(){
        if (!imgEl) return;
        imgEl.style.width = (200 * zoom.value) + 'px';
        imgEl.style.height = 'auto';
        imgEl.style.left = (parseInt(ax.value,10)||0) + 'px';
        imgEl.style.top = (parseInt(ay.value,10)||0) + 'px';
    }
    zoom?.addEventListener('input', applyTransform);
    ax?.addEventListener('input', applyTransform);
    ay?.addEventListener('input', applyTransform);
    </script>
</body>
</html>
