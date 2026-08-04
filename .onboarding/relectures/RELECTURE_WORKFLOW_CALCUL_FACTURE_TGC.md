# Relecture — WORKFLOW_CALCUL_FACTURE_TGC.md

## Verdict global
Bon — le workflow reste aligné sur le niveau de preuve du dépôt. Le fil HT/TTC, les deux taux TGC, l'arrondi et les limites explicites de l'API sont correctement rattachés à `src/InvoiceCalculator.php`, avec un usage honnête des `HYPOTHÈSE` sur les cas non observés.

## Problèmes bloquants

Aucun.

## Problèmes mineurs

Aucun.

## Points vérifiés et corrects

- Les points d'entrée sont exacts : `totalHorsTaxe(array $lignes): int` et `totalTtc(array $lignes, bool $tauxReduit = false): int` existent bien dans `src/InvoiceCalculator.php:17-30`.
- Le déroulé principal est fidèle au code : agrégation `quantite * prixUnitaire` dans `src/InvoiceCalculator.php:19-22`, choix entre `TGC_REDUIT` et `TGC_STANDARD` dans `src/InvoiceCalculator.php:28-29`, puis `round()` casté en `int` dans `src/InvoiceCalculator.php:30`.
- Les règles métier sur les deux taux disponibles, le taux standard par défaut et l'absence de panachage par ligne sont correctement déduites des constantes `TGC_STANDARD` / `TGC_REDUIT` et de la signature de `totalTtc` dans `src/InvoiceCalculator.php:11-12,26-30`.
- La formulation de l'arrondi reste au niveau de preuve local : `round()` est bien appelé sans mode explicite dans `src/InvoiceCalculator.php:30`, sans sur-affirmer un comportement externe absent du dépôt.
- Les tests appuient bien les cas nominaux revendiqués : `tests/InvoiceCalculatorTest.php:10-31` couvre le total HT (`25000`), le TTC standard (`11600`) et le TTC réduit (`10500`).
- Les risques restent honnêtes : accès directs sans garde prouvés dans `src/InvoiceCalculator.php:20-21`, comportement runtime précis laissé en `HYPOTHÈSE` dans le workflow.

## Recommandations de correction

Aucune.
