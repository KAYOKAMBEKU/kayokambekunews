## Constat Actuel
- Meta viewport présent sur les pages principales; pas de framework CSS (Bootstrap/Tailwind), CSS maison dans [style.css](file:///c:/xampp/htdocs/KAYOKA/css/style.css).
- Peu de breakpoints globaux (une media query détectée), plusieurs mises en page en CSS Grid inline sans fallback mobile.
- Largeurs/tailles fixes pour formulaires et avatars, grilles 2fr/1fr (ex. [dashboard.php](file:///c:/xampp/htdocs/KAYOKA/dashboard.php#L150-L156)), hero en 80vh sur l’accueil.
- Header/footer dupliqués dans chaque page, pas d’includes partagés.

## Objectifs
- Assurer une lisibilité et une ergonomie sur mobiles (≤576px), tablettes (≤768/992px) et desktop.
- Réduire les largeurs fixes; introduire des breakpoints cohérents et des composants réutilisables.
- Minimiser les changements intrusifs en respectant le style existant.

## Changements CSS Globaux (style.css)
- Ajouter des breakpoints standards: 1200px, 992px, 768px, 576px.
- Créer des utilitaires: .container (max-width fluide, padding), .grid-2 (2 colonnes → 1 sur <768), .grid-3 (3 → 1/2 selon breakpoint).
- Rendre les images et avatars responsives: max-width: 100%, height: auto; avatars via clamp() pour width/height.
- Harmoniser les formulaires: inputs en width: 100%, conteneur form en max-width: clamp(320px, 90vw, 720px).
- Ajuster le hero: min-height adaptatif; réduire 80vh sur petits écrans.

## Mises à Jour par Page
- Accueil ([index.php](file:///c:/xampp/htdocs/KAYOKA/index.php))
  - Remplacer styles inline de la grille par classes .grid-*; adapter le hero (min-height + padding). 
- Dashboard utilisateur ([dashboard.php](file:///c:/xampp/htdocs/KAYOKA/dashboard.php))
  - Déplacer la grille 2fr/1fr en classes .grid-2 et ajouter media queries pour <768px (stack vertical).
- Social ([social.php](file:///c:/xampp/htdocs/KAYOKA/social.php))
  - Conserver .social-layout mais uniformiser avec les utilitaires .grid-*; avatars responsives (clamp, object-fit).
- Inscription / Édition profil ([register.php](file:///c:/xampp/htdocs/KAYOKA/register.php), [edit_profile.php](file:///c:/xampp/htdocs/KAYOKA/edit_profile.php))
  - Supprimer/adapter max-width fixes (800px) vers clamp; grille du formulaire en 1 colonne sur <768.
- Admin ([admin_dashboard.php](file:///c:/xampp/htdocs/KAYOKA/admin_dashboard.php))
  - Appliquer .grid-* pour panneaux; empilement sur mobile.
- Domaines / Actualités ([domaines.php](file:///c:/xampp/htdocs/KAYOKA/domaines.php), [actualites.php](file:///c:/xampp/htdocs/KAYOKA/actualites.php), [domain.php](file:///c:/xampp/htdocs/KAYOKA/domain.php))
  - Uniformiser les listes/cartes, définir des colonnes responsives via .grid-3.

## Facteurs Transverses
- Typographie: adapter font-size via clamp() pour titres et textes importants.
- Espacements: utiliser des variables CSS (ex. --space-*) pour marges/paddings.
- Boutons: width auto, padding fluide, pas de tailles fixes.

## Mutualisation du Layout (recommandé)
- Créer header.php et footer.php pour centraliser <head>, meta viewport, liens CSS/JS, navigation.
- Inclure ces fichiers dans toutes les pages pour cohérence responsive et maintenance.

## Validation et Tests
- Vérifier aux breakpoints: 1440, 1024, 768, 576, 360 px via outils de dev.
- Tests manuels: formulaires sans overflow, grilles qui s’empilent correctement, images non étirées, hero lisible.
- Corriger régressions visuelles mineures page par page.

## Livrables
- Mise à jour de [style.css](file:///c:/xampp/htdocs/KAYOKA/css/style.css) avec utilitaires et breakpoints.
- Remplacement des styles inline par classes utilitaires sur les pages listées.
- Optionnel: nouveaux fichiers header.php/footer.php et intégration dans les pages.

Souhaitez-vous que je mette en œuvre ce plan et applique les ajustements pour rendre toutes les pages responsives ?