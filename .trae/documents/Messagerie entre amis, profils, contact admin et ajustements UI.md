## Objectifs
- Messagerie entre utilisateurs amis (chat simple, temps réel non requis) avec historique.
- Consultation des profils des utilisateurs (page dédiée), avec avatar et informations.
- Formulaire de contact pour visiteurs vers l’admin (exploite la table feedback existante).
- Logo en forme de cercle dans la barre de menu.
- Corriger la disparition du lien « Mon Compte »/tableau de bord et stabiliser la navigation.

## État actuel
- Amitiés: gérées via `friendships` (statuses pending/accepted) dans le social ([social.php](file:///c:/xampp/htdocs/KAYOKA/social.php)).
- Feedback: déjà affiché dans l’admin ([admin_dashboard.php](file:///c:/xampp/htdocs/KAYOKA/admin_dashboard.php#L287-L307)).
- Header partagé: [header.php](file:///c:/xampp/htdocs/KAYOKA/header.php) avec toggle mobile et détection de page active.
- Styles globaux: [style.css](file:///c:/xampp/htdocs/KAYOKA/css/style.css); logo actuel rectangulaire ([style.css:L58-L63](file:///c:/xampp/htdocs/KAYOKA/css/style.css#L58-L63)).

## Implémentation Messagerie
- Base de données (database.sql): créer `messages` (id, sender_id, receiver_id, content, created_at, read_at).
- Backend:
  - `send_message.php`: POST (receiver_id, content) avec contrôle d’amitié acceptée.
  - `fetch_messages.php`: GET (user_id) ou (friend_id) pour lister les échanges entre deux amis.
- UI:
  - Nouvelle page `messages.php`: liste des conversations + panneau de chat avec un ami.
  - Boutons « Message » dans [social.php](file:///c:/xampp/htdocs/KAYOKA/social.php) pour amis acceptés.
  - Rafraîchissement via requêtes AJAX simples (intervalle ou bouton « Actualiser »).

## Profils Utilisateurs
- Nouvelle page `user_profile.php?id=<userId>`: affiche avatar, nom, domaine, infos publiques.
- Liens vers profils depuis Social (posts, suggestions, demandes d’amis) et depuis `messages.php`.

## Contact Admin (Visiteurs)
- Nouvelle page `contact_admin.php`: formulaire (nom, email, message).
- Insérer dans `feedback` (déjà utilisé par l’admin) et afficher confirmation.
- Lien visible dans header (ou footer) pour visiteurs non connectés.

## Ajustements UI
- Logo circulaire:
  - Modifier `.logo-img` pour largeur=hauteur égales, `border-radius: 50%`, `object-fit: cover` dans [style.css](file:///c:/xampp/htdocs/KAYOKA/css/style.css).
- Navigation / Dashboard:
  - S’assurer que « Mon Compte » s’affiche toujours quand connecté, quelle que soit la page (vérifier [header.php](file:///c:/xampp/htdocs/KAYOKA/header.php)).
  - Revoir CSS du menu mobile pour n’affecter que <768px; garder affichage normal desktop.

## Sécurité et Règles
- Vérifier session et autorisations dans tous les endpoints.
- Échapper le contenu affiché, limiter taille des messages.

## Validation
- Tests scénarios:
  - Deux utilisateurs amis: envoi/réception de messages, consultation de profil.
  - Visiteur: envoi message admin via `contact_admin.php` et apparition dans l’admin.
  - Navigation: logo circulaire, menu opérationnel, « Mon Compte » visible.

## Livrables
- Fichiers nouveaux: `messages.php`, `send_message.php`, `fetch_messages.php`, `user_profile.php`, `contact_admin.php`.
- Mises à jour: `database.sql` (table messages), `style.css` (logo), `header.php` (lien vers Contact admin si non connecté), `social.php` (liens Message/Profil).

Souhaitez-vous que je mette en œuvre ces fonctionnalités et ajustements immédiatement ?