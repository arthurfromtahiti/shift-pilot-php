# Relecture — SECURITY_ROBUSTNESS_AUDIT.md

## Verdict global
À corriger — l'audit a le bon périmètre, mais plusieurs phrases-clés confondent absence de garde observable et comportement runtime certain. Il ajoute aussi un risque "path traversal" trop générique par rapport à la preuve disponible.

## Problèmes bloquants

- `.onboarding/audits/SECURITY_ROBUSTNESS_AUDIT.md:15` et `:48` présentent comme quasi-factuels "`Warning: Undefined array key`", "`null * null = 0`" et "continue ... sans exception". Depuis `src/InvoiceCalculator.php:22`, le seul fait prouvé est l'accès direct à deux clés sans validation. Le reste doit rester au rang d'`HYPOTHÈSE` tant que le comportement n'a pas été observé.
- `.onboarding/audits/SECURITY_ROBUSTNESS_AUDIT.md:19` affirme en `VÉRIFIÉ_CODE` que la ligne `new StreamHandler($fichier)` "est exposée à une exception non interceptée". L'absence de `try/catch` est bien visible dans `src/AppLogger.php:15-18`; l'exception effective selon la destination reste hypothétique.

## Problèmes mineurs

- `.onboarding/audits/SECURITY_ROBUSTNESS_AUDIT.md:21` et `:44` parlent de "path traversal". Avec la preuve locale, on voit surtout une écriture vers une destination arbitraire fournie par l'appelant (`src/AppLogger.php:15-18`). Le terme choisi évoque un scénario plus spécifique qui n'est pas démontré ici.

## Points vérifiés et corrects

- L'absence de secrets, d'appels réseau, de SQL et de rendu HTML est correctement corroborée par `rg --files .` puis la lecture de `src/`, `composer.json` et `phpunit.xml`.
- L'absence de validation sur valeurs négatives dans `src/InvoiceCalculator.php:18-47` est bien observée.
- Le passage libre de `$message` dans `src/AppLogger.php:26-28` est correctement distingué d'un risque d'exploitation seulement hypothétique.

## Recommandations de correction

- Requalifier en `HYPOTHÈSE` tout ce qui décrit warning, valeur `0` ou exception concrète à l'exécution.
- Remplacer "path traversal" par une formulation collée à la preuve visible, par exemple "destination de log arbitraire fournie par l'appelant".
