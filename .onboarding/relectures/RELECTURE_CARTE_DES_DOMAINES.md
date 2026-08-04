# Relecture — CARTE_DES_DOMAINES.md

## Verdict global
Bon — la carte décrit correctement un dépôt minuscule centré sur le calcul de factures TGC et isole proprement la journalisation comme domaine technique transverse. Je n'ai trouvé ni domaine inventé, ni omission métier crédible, ni granularité artificielle au vu des seuls fichiers `src/InvoiceCalculator.php` et `src/AppLogger.php`.

## Problèmes bloquants

Aucun.

## Problèmes mineurs

Aucun.

## Points vérifiés et corrects

- Le cœur métier annoncé est bien la facturation TGC : `README.md` parle de « facturation avec TGC (Polynésie française) », `composer.json` reprend « facturation TGC », `src/InvoiceCalculator.php` implémente `totalHorsTaxe()` et `totalTtc()` avec `TGC_STANDARD = 0.16` et `TGC_REDUIT = 0.05`, et `tests/InvoiceCalculatorTest.php` prouve les cas `25000`, `11600` et `10500`.
- Le domaine `facturation-tgc` n'est pas plaqué : le grep `rg -n "InvoiceCalculator|TGC_|totalHorsTaxe|totalTtc|facture"` ne matche que `src/InvoiceCalculator.php`, ses tests, et le contexte descriptif du dépôt (`README.md`). Il ne recouvre pas un pan hétérogène du code.
- Le domaine `journalisation-applicative` existe réellement et reste séparé du métier : `src/AppLogger.php` encapsule `Monolog\Logger` et `StreamHandler`, expose `factureEmise()` et `erreurCalcul()`, et `rg -n "logger|log|stderr|Monolog|StreamHandler|addInfo|addError"` montre que ce périmètre reste concentré dans ce fichier, plus la dépendance déclarée dans `composer.json` et le rappel de stack dans `README.md`.
- La carte ne prétend pas à tort une couche HTTP, une persistance, des routes, ou des entités ORM : `rg --files .` ne remonte que `README.md`, `composer.json`, `phpunit.xml`, `src/InvoiceCalculator.php`, `src/AppLogger.php` et `tests/InvoiceCalculatorTest.php`.
- La granularité à 2 domaines est justifiée par la taille réelle du dépôt. Je n'ai trouvé aucun troisième pan autonome dans `src/` ou `tests/` qui imposerait de scinder davantage la carte.

## Recommandations de correction

- Publier telle quelle. Pour les étapes aval, conserver explicitement la distinction entre domaine métier (`facturation-tgc`) et domaine technique transverse (`journalisation-applicative`) afin d'éviter de surinterpréter la portée fonctionnelle du dépôt.
