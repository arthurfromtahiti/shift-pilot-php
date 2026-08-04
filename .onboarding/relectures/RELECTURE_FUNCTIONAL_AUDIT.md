# Relecture — FUNCTIONAL_AUDIT.md

## Verdict global
À corriger — le raisonnement fonctionnel est utile, mais l'audit sur-affirme un point réglementaire externe comme du `VÉRIFIÉ_CODE`. Le reste est plutôt propre, mais ce défaut touche le cœur du constat métier.

## Problèmes bloquants

- `.onboarding/audits/FUNCTIONAL_AUDIT.md:17` et `:32` qualifient en `VÉRIFIÉ_CODE` que `0.16` et `0.05` "correspondent aux taux de la TGC polynésienne en vigueur". `src/InvoiceCalculator.php:11-12` ne prouve que la présence de ces constantes ; le mot "en vigueur" introduit un fait réglementaire externe et daté, non sourcé dans l'artefact. Au mieux, cela doit devenir `HYPOTHÈSE` ou contexte externe explicite.

## Problèmes mineurs

- `.onboarding/audits/FUNCTIONAL_AUDIT.md:25` tire `PHP_ROUND_HALF_UP` de l'appel `round()` sans deuxième argument. L'observation solide est que le mode n'est pas explicité dans `src/InvoiceCalculator.php:30`; la règle exacte relève d'un contexte langage, pas du dépôt.

## Points vérifiés et corrects

- La limitation "taux unique par facture" est correctement déduite de `src/InvoiceCalculator.php:25-29`.
- L'absence d'intégration visible entre `InvoiceCalculator` et `AppLogger` est correctement établie par l'inventaire des fichiers du dépôt et la lecture de `src/` et `tests/`.
- Le comportement sur tableau vide est correctement inféré du code en restant local : boucle vide puis `round(0)` dans `src/InvoiceCalculator.php:17-30`.

## Recommandations de correction

- Requalifier tout le passage sur les taux "en vigueur" en hypothèse ou en contexte externe.
- Garder l'analyse fonctionnelle centrée sur les capacités prouvées par l'API publique plutôt que sur la conformité réglementaire non sourcée.
