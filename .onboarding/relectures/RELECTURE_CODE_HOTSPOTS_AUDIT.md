# Relecture — CODE_HOTSPOTS_AUDIT.md

## Verdict global
À corriger — plusieurs constats centraux présentent du comportement d'exécution comme du `VÉRIFIÉ_CODE`. Le fond de l'audit est globalement pertinent, mais la discipline de preuve n'est pas tenue sur les points les plus sensibles.

## Problèmes bloquants

- `.onboarding/audits/CODE_HOTSPOTS_AUDIT.md:15` part d'une observation juste (`src/InvoiceCalculator.php:19-22` ne valide pas les clés) puis conclut en `VÉRIFIÉ_CODE` qu'une ligne `qty` "obtient un calcul de 0". Ce résultat précis n'est pas lisible dans le code seul. Sans exécution, on peut affirmer l'absence de garde ; on ne peut pas présenter comme fait le comportement runtime exact.
- Même glissement en `.onboarding/audits/CODE_HOTSPOTS_AUDIT.md:21` et `:44` : "exposée à une exception non interceptée" et "`null * null = 0` sans exception" dépassent `src/AppLogger.php:15-18` et `src/InvoiceCalculator.php:21`. Le `try/catch` absent est `VÉRIFIÉ_CODE`; l'exception effectivement levée et l'arithmétique sur clés manquantes restent des `HYPOTHÈSE` tant qu'elles ne sont pas observées.

## Problèmes mineurs

- `.onboarding/audits/CODE_HOTSPOTS_AUDIT.md:17` qualifie `PHP_ROUND_HALF_UP` comme allant de soi à partir de `round()` sans deuxième argument. C'est raisonnable, mais ce n'est pas une preuve issue du dépôt ; garder la focalisation sur "mode non explicite" serait plus rigoureux.

## Points vérifiés et corrects

- Le point chaud métier est correctement localisé dans `src/InvoiceCalculator.php:19-22`, unique boucle d'agrégation du dépôt.
- L'absence de tests sur `AppLogger` est correctement recoupée avec `rg --files .` puis `tests/InvoiceCalculatorTest.php`, seul fichier sous `tests/`.
- La dépendance directe à l'API Monolog 1.x est bien sourcée par `src/AppLogger.php:9,23,28` et `composer.json:8`.

## Recommandations de correction

- Réécrire les constats de runtime en séparant strictement : `VÉRIFIÉ_CODE` pour l'absence de garde ou de `try/catch`, `HYPOTHÈSE` pour le warning, la valeur calculée ou l'exception effectivement produite.
- Remplacer les exemples comportementaux non observés par des formulations du type "peut conduire à" ou "reste à confirmer à l'exécution".
