# Relecture — CARTE_DES_DOMAINES.md

## Verdict global

**Acceptable avec réserves** — les deux domaines sont réels, distincts et correctement dimensionnés pour ce dépôt minuscule. La carte doit toutefois être corrigée sur deux formulations qui transforment des limites de preuve ou une API isolée en affirmations trop fortes ; resoumettre ensuite en `in_review`.

## Problèmes bloquants

Aucun domaine inventé ou oublié n'a été établi. Le parcours de l'arbre `git ls-tree -r HEAD` montre uniquement `src/InvoiceCalculator.php`, `src/AppLogger.php` et leurs tests, sans contrôleur, route, ORM, persistance, job ou troisième sous-système métier autonome. Le plafond/plancher usuel de 4 domaines est donc honnêtement expliqué par la matière disponible, et scinder HT/TTC serait une granularité de module, non de domaine.

## Problèmes mineurs

- **Contradiction sur le signal base/schema.** La carte affirme dans `facturation-tgc` et dans sa conclusion qu'« aucun des trois signaux » n'est trouvé, tout en précisant dans *Incertitudes* que le signal *schéma* est non évaluable faute d'accès au schéma. Ces deux propositions ne sont pas équivalentes. Preuve : `src/` et l'arbre Git permettent de vérifier l'absence de code ORM/renderer, mais aucun fichier de schéma ou accès à une base n'a été ouvert. Reformuler en : « aucun signal observable dans le code ; le signal schéma/base reste non évalué » et ne pas classer ce point comme absence établie.

- **Journalisation présentée comme intégrée.** La phrase d'introduction (« encadre l'émission des factures et les erreurs de calcul ») et certains workflows attendus suggèrent une journalisation branchée sur le calcul. Or `rg -n "AppLogger|factureEmise|erreurCalcul" src tests` ne trouve que les déclarations et méthodes de `src/AppLogger.php`; `src/InvoiceCalculator.php` n'importe ni n'instancie `AppLogger`. Le domaine `journalisation-applicative` est bien réel (constructeur, `Logger`, `StreamHandler`, `info`, `error` dans `src/AppLogger.php`), mais il faut le décrire comme composant/API de journalisation disponible, sans affirmer un appel effectif lors d'un calcul.

## Points vérifiés et corrects

- `facturation-tgc` est prouvé par `src/InvoiceCalculator.php` : constantes `TGC_STANDARD`/`TGC_REDUIT`, méthodes publiques `totalHorsTaxe`/`totalTtc`, taux par ligne, arrondi et exceptions ; `tests/InvoiceCalculatorTest.php` contient 12 méthodes de test couvrant standard, réduit, mixte, défaut, entrées invalides et débordement.
- Le cœur de facturation n'est pas une catégorie plaquée : `README.md`, `composer.json`, le docblock de `InvoiceCalculator` et les tests convergent sur la facturation TGC.
- `journalisation-applicative` est un domaine technique transverse réel et correctement séparé du métier : `src/AppLogger.php` encapsule Monolog 3.x et `composer.json`/`composer.lock` prouvent la dépendance (`^3.0`, version verrouillée `3.10.0`).
- Les indices de rattachement sont suffisamment discriminants : `rg` sur `InvoiceCalculator|TGC_|totalHorsTaxe|totalTtc` et sur `AppLogger|Monolog|StreamHandler` reste concentré sur les fichiers correspondants, leurs tests ou la configuration.
- La granularité est juste pour un dépôt de deux classes : aucune découpe supplémentaire crédible n'est imposée par `git ls-tree -r HEAD`.
- Les routes, entités et dépendances base ne sont pas inventées : aucune route/HTTP/ORM/SQL/PDO n'est localisée dans l'arbre courant.

## Recommandations de correction

Corriger les deux formulations ci-dessus, conserver les deux domaines et abaisser la portée de la preuve base à « non évaluée pour le schéma/runtime ». Ne pas ajouter de domaine « PHPUnit », « Composer » ou « Monolog » autonome : ce sont respectivement de l'outillage, une dépendance et un composant transverse déjà correctement représenté.
