# Relecture — WORKFLOW_CALCUL_FACTURE_TGC.md

## Verdict global
Bon — le workflow reste aligné sur le niveau de preuve du dépôt. Le fil HT/TTC, les deux taux TGC, l'arrondi et les limites explicites de l'API sont correctement rattachés à `src/InvoiceCalculator.php`, avec un usage honnête des `HYPOTHÈSE` sur les cas non observés.

## Problèmes bloquants

Aucun.

## Problèmes mineurs

Aucun.

## Points vérifiés et corrects

- Les points d'entrée sont exacts : `totalHorsTaxe(array $lignes): int` (ligne 18) et `totalTtc(array $lignes, bool $tauxReduit = false): int` (ligne 35) existent bien dans `src/InvoiceCalculator.php`.
- Le déroulé principal est fidèle au code : agrégation `quantite * prixUnitaire` dans `src/InvoiceCalculator.php:21-26`, choix entre `TGC_REDUIT` et `TGC_STANDARD` dans `src/InvoiceCalculator.php:42`, puis `round()` casté en `int` dans `src/InvoiceCalculator.php:27,45`.
- Les règles métier sur les deux taux disponibles, le taux standard par défaut et l'absence de panachage par ligne sont correctement déduites des constantes `TGC_STANDARD` / `TGC_REDUIT` (lignes 11-12) et de la signature de `totalTtc` (ligne 35).
- La formulation de l'arrondi reste au niveau de preuve local : `(int) round()` est bien appelé explicitement dans `src/InvoiceCalculator.php:27` et `45`, sans sur-affirmer un comportement externe absent du dépôt.
- Les tests appuient bien les cas nominaux revendiqués : `tests/InvoiceCalculatorTest.php` couvre le total HT (`25000`), le TTC standard (`11600`) et le TTC réduit (`10500`).
- Les risques restent honnêtes : accès directs sans garde prouvés dans `src/InvoiceCalculator.php:22`, comportement runtime précis laissé en `HYPOTHÈSE` dans le workflow.

## Recommandations de correction

Aucune.
