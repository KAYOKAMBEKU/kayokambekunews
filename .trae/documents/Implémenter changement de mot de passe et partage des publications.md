## Objectifs
- Ajouter changement de mot de passe pour tout utilisateur (y compris admin).
- Rendre le partage de publications opérationnel (liens publics, boutons sociaux, copie du lien).

## Contexte Code
- Connexion/inscription: [auth_login.php](file:///c:/xampp/htdocs/KAYOKA/auth_login.php), [process_register.php](file:///c:/xampp/htdocs/KAYOKA/process_register.php).
- Création de publication: [dashboard.php:L22-L85](file:///c:/xampp/htdocs/KAYOKA/dashboard.php#L22-L85); fil: [social.php](file:///c:/xampp/htdocs/KAYOKA/social.php).
- Le bouton « Partager » affiche une alerte (placeholder) : [social.php:L260-L262](file:///c:/xampp/htdocs/KAYOKA/social.php#L260-L262).

## Changement de Mot de Passe (utilisateur/admin)
1. Page protégée: change_password.php
   - Formulaire: mot de passe actuel, nouveau, confirmation.
2. Endpoint: process_change_password.php
   - Vérifie l’authentification (session), récupère l’utilisateur.
   - Valide le mot de passe actuel via password_verify.
   - Valide le nouveau: longueur ≥ 8, au moins une lettre et un chiffre.
   - Met à jour users.password via password_hash.
   - Retourne messages de succès/erreur cohérents.
3. Intégration UI
   - Lien « Changer le mot de passe » dans [header.php](file:///c:/xampp/htdocs/KAYOKA/header.php) (visible connecté) et sur [edit_profile.php](file:///c:/xampp/htdocs/KAYOKA/edit_profile.php).
   - Pour admin, même page accessible depuis [admin_dashboard.php](file:///c:/xampp/htdocs/KAYOKA/admin_dashboard.php).

## Réinitialisation en cas d’oubli (option utile)
1. Table password_resets (user_id, token, expires_at, used_at).
2. Pages
   - forgot_password.php: saisie email → process_forgot_password.php (génère token, enregistre, envoie lien; en dev, affiche le lien).
   - reset_password.php: form nouveau mot de passe avec token → process_reset_password.php (valide token/expiration, applique mise à jour).

## Partage des Publications
1. Page publique du post: post.php
   - Affiche un post approuvé (status = 'approved') par id.
   - Rend le média (image/vidéo/audio/document) et texte.
   - Balises Open Graph (title, description, image) pour un meilleur rendu sur réseaux.
2. Génération d’URL
   - Canonique: /post.php?id=POST_ID.
   - Vérifie que le post est approuvé; sinon 404/accès refusé.
3. Boutons de partage dans le fil
   - Remplacer l’alerte par:
     - Copier le lien (clipboard) vers l’URL canonique.
     - Liens préremplis: WhatsApp, Facebook, Twitter (texte + URL encodée).
   - Emplacement: section actions des cartes dans [social.php].
4. (Option) Compteur de partages
   - Colonne shares_count dans posts; endpoint léger pour incrément après action.

## Sécurité & Robustesse Minimales
- Actions sensibles en POST avec jeton CSRF (like/unlike, création, suppression, changement de mot de passe).
- Uploads: vérifier MIME réel, taille max, whitelist extensions; renommage sécurisé (uniqid) et suppression du fichier lors de la suppression du post.
- Validation stricte: domain_id existant, media_type connu; messages d’erreur clairs.

## Tests & Validation
- Mot de passe: succès, échec password actuel, échec complexité.
- Reset: token valide/expiré/invalide.
- Partage: accès post approuvé, rendu OG, copier lien, liens sociaux.

## Livrables
- Nouveaux: change_password.php, process_change_password.php, (optionnels) forgot_password.php, process_forgot_password.php, reset_password.php, process_reset_password.php, post.php, helpers/csrf.php.
- Modif: header.php (liens), edit_profile.php (lien), admin_dashboard.php (lien), social.php (UI partage), dashboard.php (upload basique renforcé).

Si vous confirmez, j’implémente immédiatement et vérifie en local.