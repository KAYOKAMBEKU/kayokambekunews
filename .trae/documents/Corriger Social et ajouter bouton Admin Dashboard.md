## Problèmes observés
- La page Social ne s’affiche pas à cause d’une erreur de syntaxe PHP introduite récemment: chaîne SQL écrite avec des \" (échappements) au lieu de " (quotes) dans [social.php](file:///c:/xampp/htdocs/KAYOKA/social.php#L248-L257), ce qui casse l’exécution.
- Pas de bouton global permettant à un admin de revenir au tableau de bord admin depuis n’importe quelle page.

## Changements proposés
- Corriger Social:
  - Remplacer la ligne de préparation SQL en utilisant des quotes standard: `$stmt2 = $pdo->prepare("SELECT...")` → `$stmt2 = $pdo->prepare("SELECT...")` sans backslashes, et corriger les attributs HTML autour du bouton Message pour retirer les \".
  - Vérifier l’exécution et l’affichage du fil après correction.
- Ajouter un bouton Admin Dashboard:
  - Dans [header.php](file:///c:/xampp/htdocs/KAYOKA/header.php): si `$_SESSION['user_role'] === 'admin'`, afficher un lien « Administration » pointant vers [admin_dashboard.php](file:///c:/xampp/htdocs/KAYOKA/admin_dashboard.php), visible partout.
  - Conserver « Mon Compte » pour les utilisateurs; pour les admins, avoir à la fois « Messages », « Mon Compte » et « Administration ».

## Validation
- Ouvrir Social pour vérifier qu’elle s’affiche et que le bouton « Message » apparaît seulement quand les utilisateurs sont amis.
- Vérifier qu’un admin voit « Administration » dans le header sur toutes les pages et qu’il retourne bien sur le tableau de bord.

Souhaitez-vous que je procède à ces corrections immédiatement ?