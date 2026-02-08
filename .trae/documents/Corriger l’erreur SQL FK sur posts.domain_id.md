## Constat
- Erreur SQL: violation de contrainte étrangère lors de l’insertion dans posts (fk_post_domain sur `domain_id`).
- Code [dashboard.php](file:///c:/xampp/htdocs/KAYOKA/dashboard.php#L63-L65) insère `domain_id`, `media_type`, `title`.
- Schéma actuel peut avoir `domain_id` défini NOT NULL ou reçoit une valeur invalide (""/0) au lieu de NULL.

## Corrections proposées
- Schéma base:
  - S’assurer que `posts.domain_id` est NULLABLE et FK vers `domains(id)` avec ON DELETE SET NULL.
  - S’assurer que les colonnes `title` (VARCHAR) et `media_type` (ENUM/VARCHAR) existent, car utilisées par le code et par [social.php](file:///c:/xampp/htdocs/KAYOKA/social.php#L212-L218).
  - Mise à jour de database.sql: ALTER/CREATE posts avec `domain_id INT NULL DEFAULT NULL, title VARCHAR(255) NULL, media_type ENUM('image','video','audio','document') NULL`, et FK `fk_post_domain`.
- Code insertion:
  - Dans [dashboard.php](file:///c:/xampp/htdocs/KAYOKA/dashboard.php#L22-L71): normaliser `domain_id` en entier; si <=0, utiliser `NULL`.
  - Préserver `media_type` conforme à la liste autorisée; défaut à 'image' si invalide.
  - Conserver l’upload et faire l’INSERT avec valeur `NULL` pour `domain_id` si aucun domaine choisi.
- Journalisation/retour utilisateur:
  - En cas d’erreur PDO, afficher un message utilisateur clair et ne pas interrompre la page.

## Validation
- Essai d’insertion d’un post sans domaine: doit réussir (domain_id = NULL).
- Essai avec un domaine existant: doit réussir.
- Fil Social: filtres domain/media_type fonctionnent; aucune erreur d’affichage.

Souhaitez-vous que je applique ces corrections tout de suite ?