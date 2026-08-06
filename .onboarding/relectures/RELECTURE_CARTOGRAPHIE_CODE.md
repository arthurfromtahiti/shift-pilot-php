# Relecture — CARTOGRAPHIE_CODE.md

## Verdict global
Acceptable avec réserves — la cartographie est majoritairement fidèle au code et bien sourcée. Les réserves portent surtout sur quelques glissements vers des effets runtime non prouvés ou des jugements de risque formulés trop affirmativement.

## Problèmes bloquants

## Problèmes mineurs
- `clé manquante = résultat faussé sans erreur` dans les points critiques de `InvoiceCalculator` va plus loin que la preuve disponible : le code prouve l'absence de garde (`src/InvoiceCalculator.php:22`), pas l'effet runtime exact. Le workflow calcul restait prudemment en hypothèse sur ce point.
- `Rupture lors de migration 2.x` pour Monolog est formulé comme conséquence certaine alors que la carte des domaines avait explicitement requalifié ce sujet en hypothèse externe non prouvable depuis le dépôt ; sources : `.onboarding/domaines/CARTE_DES_DOMAINES.md`, `src/AppLogger.php:9`, `composer.json:8`.
- `Supporte PHPUnit 9 ou 10` est inexact au regard de `composer.json`, qui fixe `phpunit/phpunit: ^9.6` uniquement ; preuve : `composer.json`.

## Points vérifiés et corrects
- La vue d'ensemble de l'arborescence et la taille relative du dépôt sont cohérentes avec l'arbre lu.
- Les descriptions des classes `InvoiceCalculator` et `AppLogger`, de leurs signatures, constantes et dépendances, sont fidèles au code (`src/InvoiceCalculator.php`, `src/AppLogger.php`).
- L'absence de tests `AppLogger`, de couverture configurée dans `phpunit.xml`, et d'orchestration interne entre les deux classes est correctement relevée.

## Recommandations de correction
- Corriger les formulations qui attribuent un comportement runtime précis là où seule l'absence de garde est prouvée.
- Corriger la ligne sur PHPUnit pour refléter `^9.6`.
- Sur Monolog 2.x, rester sur une formulation d'hypothèse ou de point de vigilance externe, pas sur une rupture certaine.
