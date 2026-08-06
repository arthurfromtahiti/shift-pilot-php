# Relecture — CODE_HOTSPOTS_AUDIT.md

## Verdict global
À corriger — plusieurs constats centraux présentent du comportement d'exécution comme du `VÉRIFIÉ_CODE`. Le fond de l'audit est globalement pertinent, mais la discipline de preuve n'est pas tenue sur les points les plus sensibles.

## Problèmes bloquants

- L'audit sépare maintenant strictement les faits vérifiables (absence de `isset()`, absence de `try/catch`, appels à `round()` sans mode) des hypothèses sur le comportement runtime. Les boucles et conditions se trouvent maintenant à `src/InvoiceCalculator.php:21-26` et `27,45`.

## Problèmes mineurs

## Points vérifiés et corrects

- Le point chaud métier est correctement localisé dans `src/InvoiceCalculator.php:21-26`, unique boucle d'agrégation du dépôt. L'arrondi explicite aux lignes 27 et 45 élimine toute ambiguïté sur la coercition.
- L'absence de tests sur `AppLogger` est correctement recoupée avec `rg --files .` puis `tests/InvoiceCalculatorTest.php`, seul fichier sous `tests/`.
- La dépendance directe à l'API Monolog 1.x est bien sourcée par `src/AppLogger.php:9,23,28` et `composer.json:8`.

## Recommandations de correction

- Réécrire les constats de runtime en séparant strictement : `VÉRIFIÉ_CODE` pour l'absence de garde ou de `try/catch`, `HYPOTHÈSE` pour le warning, la valeur calculée ou l'exception effectivement produite.
- Remplacer les exemples comportementaux non observés par des formulations du type "peut conduire à" ou "reste à confirmer à l'exécution".
