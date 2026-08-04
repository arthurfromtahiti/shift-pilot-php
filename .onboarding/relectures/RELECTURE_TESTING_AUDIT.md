# Relecture — TESTING_AUDIT.md

## Verdict global
Acceptable avec réserves — la lecture de la suite de tests est globalement juste et honnête sur le fait qu'aucune exécution n'a été observée. Quelques puces de cas limites glissent toutefois vers des comportements runtime non prouvés.

## Problèmes bloquants

Aucun.

## Problèmes mineurs

- `.onboarding/audits/TESTING_AUDIT.md:25-27` décrit des "comportements réels" pour clé manquante, valeurs négatives ou types incorrects. Comme l'en-tête du fichier rappelle lui-même qu'aucune exécution n'a été observée (`.onboarding/audits/TESTING_AUDIT.md:3`), ces puces devraient être formulées comme hypothèses de comportement, pas comme faits.
- `.onboarding/audits/TESTING_AUDIT.md:61` recommande `TestHandler` comme disponible dans Monolog. C'est plausible, mais la disponibilité exacte n'est pas démontrée par la seule lecture de `composer.json` et du dépôt.

## Points vérifiés et corrects

- Le constat "3 tests nominaux, aucun test AppLogger" est correctement fondé sur `tests/InvoiceCalculatorTest.php` et l'absence d'autre fichier sous `tests/`.
- L'absence de section `<coverage>` dans `phpunit.xml` est correctement observée.
- L'absence de `composer.lock` est bien reliée à `README.md:10`.

## Recommandations de correction

- Requalifier les puces de cas limites en `HYPOTHÈSE de comportement à l'exécution`.
- Conserver tel quel le cœur de l'audit sur le périmètre de test, qui est solide.
