## Problème
- La navigation n’est pas adaptée aux smartphones et il manque un toggle (hamburger) sur la barre de menu.
- Le JavaScript existant ([script.js](file:///c:/xampp/htdocs/KAYOKA/js/script.js)) ne gère que l’inscription; aucune logique de menu.

## Objectif
- Ajouter un bouton toggle (hamburger) sur toutes les pages, pour afficher/masquer le menu de navigation sur mobiles.
- Garantir une expérience accessible (ARIA), fiable sans régressions, et un comportement sans-JS acceptable.

## Modifications HTML
- Mettre à jour [header.php](file:///c:/xampp/htdocs/KAYOKA/header.php):
  - Ajouter un bouton .menu-toggle (icône bars Font Awesome), aria-controls="main-nav", aria-expanded="false".
  - Donner un id au conteneur de navigation (ex: id="main-nav").
- Inclure le script global dans [footer.php](file:///c:/xampp/htdocs/KAYOKA/footer.php): <script src="js/script.js"></script> pour que le toggle fonctionne partout.

## Modifications CSS (style.css)
- Sous 768px:
  - Afficher .menu-toggle.
  - Empiler verticalement les liens du menu.
  - Masquer le menu uniquement quand la classe .has-js est présente (donc si JS est actif).
- En état "ouvert": quand .nav-open est appliqué au header, montrer le menu.
- Transitions simples et z-index pour éviter recouvrement.

## Modifications JS (script.js)
- Ajouter une initialisation globale:
  - Ajouter la classe .has-js au <html>.
  - Gérer le click sur .menu-toggle: toggler .nav-open sur le header, mettre à jour aria-expanded.
  - Fermer le menu sur "Escape" et lors d’un clic en dehors.
- Garder le code d’inscription existant.

## Accessibilité
- aria-controls/aria-expanded sur le bouton; focus conservé; fermeture via Escape.

## Portée
- Fonctionne sur toutes les pages qui incluent [header.php] et [footer.php] (déjà intégré).

## Validation
- Tester sur largeurs 360/576/768/1024:
  - Bouton visible <768px, menu masqué par défaut et s’ouvre au tap.
  - Liens cliquables, pas d’overflow ni recouvrement.
  - Sans JS: le menu reste visible sur mobile (fallback sûr).

## Livrables
- Mise à jour de header.php, style.css et script.js.
- Vérification multi-pages et ajustement mineur des espacements si nécessaire.

Souhaitez-vous que je mette en œuvre ces changements pour ajouter le toggle mobile partout ?