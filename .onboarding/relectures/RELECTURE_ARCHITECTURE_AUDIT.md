# Relecture — ARCHITECTURE_AUDIT.md

## Verdict global
Acceptable avec réserves — le cadrage général du dépôt et la séparation `InvoiceCalculator` / `AppLogger` sont justes, mais le texte glisse par endroits du code lu vers des effets runtime ou des faits externes non prouvés. Le producteur doit resserrer ces formulations avant publication.

## Problèmes bloquants

Aucun.

## Problèmes mineurs

- Le résumé exécute une inférence externe comme un fait : `.onboarding/audits/ARCHITECTURE_AUDIT.md:11` affirme que l'API `addInfo`/`addError` "a été retirée en Monolog 2.0" puis que la bibliothèque "cesse de fonctionner silencieusement". Dans le dépôt, on peut seulement vérifier `composer.json:8` et `src/AppLogger.php:9,23,28`. La suppression en Monolog 2.x et le mode exact de rupture relèvent d'une `HYPOTHÈSE`, d'autant que la même section détaille ensuite une exception possible, donc pas une panne "silencieuse".
- La rubrique `Questions ouvertes` ajoute un fait temporel externe sans source locale : `.onboarding/audits/ARCHITECTURE_AUDIT.md:59` dit que "PHP 8.0 est en fin de vie". Rien dans `composer.json:6`, `README.md` ou `src/` ne permet de le qualifier en `VÉRIFIÉ_CODE`. Si ce point reste, il doit être explicitement présenté comme contexte externe.

## Points vérifiés et corrects

- Le cadrage "bibliothèque PHP pure" est fidèle au dépôt : `rg --files .` ne remonte que `README.md`, `composer.json`, `phpunit.xml`, `src/InvoiceCalculator.php`, `src/AppLogger.php` et `tests/InvoiceCalculatorTest.php`, sans contrôleur, route, migration ni framework.
- La séparation des responsabilités est correctement sourcée : `src/InvoiceCalculator.php:1-30` ne référence pas `AppLogger`, et `src/AppLogger.php:1-28` ne référence pas `InvoiceCalculator`.
- L'absence d'interface et d'injection sur `AppLogger` est bien observée dans `src/AppLogger.php:15-18`.
- L'absence de `composer.lock` est correctement rattachée au dépôt via `README.md:10`.

## Recommandations de correction

- Requalifier en `HYPOTHÈSE` toutes les phrases sur Monolog 2.x qui dépassent `composer.json:8` et `src/AppLogger.php:9,23,28`, y compris le mode de panne.
- Supprimer ou requalifier le point sur la fin de vie de PHP 8.0 s'il n'est pas sourcé dans l'artefact.
