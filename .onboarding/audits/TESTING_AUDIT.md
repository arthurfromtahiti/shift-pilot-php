# Tests — Audit

> Confiance : high — le fichier de test et la configuration PHPUnit ont été lus intégralement. L'exécution réelle des tests (sorties PHPUnit) n'a pas été observée dans ce run (vendor non installé dans le checkout) ; les statuts des tests sont basés sur la lecture du source (`VÉRIFIÉ_CODE` du code de test) et non sur une exécution (`OBSERVÉ`).

## Compréhension globale

La couverture de test est **partielle et non mesurée** : 3 cas de test PHPUnit couvrent le chemin nominal d'`InvoiceCalculator`, et `AppLogger` est totalement absent des tests. La configuration PHPUnit (`phpunit.xml`) n'active pas la collecte de couverture de code. L'absence de `composer.lock` rend les résultats de test non reproductibles entre environnements (Monolog peut varier dans la plage `^1.25`). Le projet est un pilote minimal et ses tests sont cohérents avec cette ambition — mais ils ne constituent pas un filet de sécurité suffisant pour une future évolution.

## Résumé exécutif

Trois tests couvrent les cas nominaux de l'unique fonction métier : HT avec deux lignes, TTC au taux standard, TTC au taux réduit. Ils sont corrects et leurs assertions sont vérifiées par le calcul (`25000 = 2×10000 + 1×5000`, `11600 = 10000 × 1.16`, `10500 = 10000 × 1.05`). La classe `AppLogger` — seul composant technique du projet — n'a aucun test dédié : ni test de construction, ni test de délégation aux méthodes Monolog, ni test du comportement sur flux inaccessible. Les cas limites d'`InvoiceCalculator` (tableau vide, clés manquantes, valeurs négatives, taux mixtes) ne sont pas testés. `phpunit.xml` n'est pas configuré pour générer un rapport de couverture (`coverage` non activé). L'absence de `composer.lock` expose les tests à des variations de comportement de Monolog dans la plage `^1.25`.

## Constats détaillés

**Trois tests nominaux, tous corrects.** `VÉRIFIÉ_CODE` — `tests/InvoiceCalculatorTest.php` contient trois méthodes :
- `testTotalHorsTaxe` : deux lignes (2×10000 + 1×5000), assertion `assertSame(25000, ...)` — calcul vérifié par arithmétique : `2×10000 + 1×5000 = 25000`. ✓
- `testTotalTtcTauxStandard` : une ligne de 10000, assertion `assertSame(11600, ...)` — `(int) round(10000 × 1.16) = 11600`. ✓
- `testTotalTtcTauxReduit` : une ligne de 10000, assertion `assertSame(10500, ...)` — `(int) round(10000 × 1.05) = 10500`. ✓
Les trois tests utilisent `assertSame` (comparaison stricte de type et valeur) — choix correct pour des résultats de type `int`.

**`AppLogger` : zéro test.** `VÉRIFIÉ_CODE` — recherche sur `grep -r 'AppLogger' tests/` : aucune occurrence localisée dans `tests/InvoiceCalculatorTest.php` et aucun autre fichier de test dans le répertoire `tests/`. La classe `AppLogger` — incluant son constructeur, `factureEmise` et `erreurCalcul` — est totalement exclue de la suite de tests. Si l'API Monolog est migrée (`addInfo` → `info`), les tests n'intercepteront pas la régression.

**Cas limites non couverts pour `InvoiceCalculator`.** `VÉRIFIÉ_CODE` — aucun des cas suivants n'est testé dans `tests/InvoiceCalculatorTest.php` :
- Tableau vide `[]` — comportement probable par lecture du code : la boucle ne s'exécute pas et retourne `0`, mais ce n'est pas prouvé par un test.
- Clé manquante dans une ligne — `HYPOTHÈSE de comportement à l'exécution` : peut produire un `Warning` PHP et un calcul faussé ; non observé à l'exécution.
- Valeur `quantite` ou `prixUnitaire` négative — `HYPOTHÈSE de comportement à l'exécution` : un HT négatif serait probablement retourné sans erreur ; non observé à l'exécution.
- Ligne avec des types incorrects (ex. `prixUnitaire: "dix mille"`) — `HYPOTHÈSE de comportement à l'exécution` : erreur de type ou coercition selon le contexte PHP 8.x ; non observé.
- Taux `$tauxReduit = true` combiné à des lignes mixtes — non applicable (limitation architecturale, pas un cas testable).

**Configuration PHPUnit sans collecte de couverture.** `VÉRIFIÉ_CODE` — `phpunit.xml` configure uniquement la testsuite et le bootstrap (`vendor/autoload.php`) ; aucun élément `<coverage>` n'est présent. La couverture réelle (lignes, branches) n'est ni collectée ni reportée. `HYPOTHÈSE` : Xdebug ou PCOV peuvent être disponibles dans certains environnements, mais sans configuration, la commande `composer test` ne produit aucun rapport de couverture.

**`composer.lock` absent : tests non reproductibles.** `VÉRIFIÉ_CODE` — `README.md:13` mentionne explicitement l'absence de `composer.lock`. Sans ce fichier, deux exécutions de `composer install` (CI vs local, avec des dates différentes) peuvent installer des versions différentes de Monolog dans la plage `^1.25`. Si une version mineure de Monolog introduisait un changement de comportement sur `addInfo`/`addError`, les tests passeraient dans un environnement et échoueraient dans l'autre — sans lien apparent avec le code du dépôt.

**Namespace de test cohérent.** `VÉRIFIÉ_CODE` — `tests/InvoiceCalculatorTest.php:3` déclare `namespace App\Tests`, cohérent avec `composer.json:17` (`"App\\Tests\\"` → `tests/`). La classe étend `PHPUnit\Framework\TestCase` et est marquée `final` — choix corrects.

## Forces

- `VÉRIFIÉ_CODE` : les trois tests nominaux sont corrects, utilisent `assertSame` (comparaison stricte), et couvrent les deux taux TGC — la couverture du chemin nominal est complète.
- `VÉRIFIÉ_CODE` : la commande `composer test` est définie dans `composer.json:19` — un nouveau développeur peut lancer les tests en une commande.
- `VÉRIFIÉ_CODE` : la suite est déclarée dans `phpunit.xml` avec `bootstrap="vendor/autoload.php"` — pas de magie implicite dans la configuration.

## Dettes techniques

- `VÉRIFIÉ_CODE` : `AppLogger` entièrement non testé — `tests/` ne contient aucun test pour cette classe.
- `VÉRIFIÉ_CODE` : aucun test de cas limite sur `InvoiceCalculator` (tableau vide, clés manquantes, valeurs négatives).
- `VÉRIFIÉ_CODE` : pas de collecte de couverture dans `phpunit.xml` — la couverture réelle est inconnue.
- `VÉRIFIÉ_CODE` : `composer.lock` absent — reproductibilité des tests non garantie.

## Zones critiques

- **`tests/InvoiceCalculatorTest.php`** — unique fichier de test : toute évolution du projet qui n'ajoute pas de tests ici élargit une zone déjà non couverte. Un senior qui reprend le projet regarderait en premier ce fichier et constaterait que 100 % des tests testent un seul chemin nominal sur une seule classe.
- **Absence totale de test `AppLogger`** — si la migration Monolog 2.x est réalisée, les tests actuels ne la valideront pas. La régression ne sera détectée qu'à l'exécution de l'application hôte.

## Risques

- `VÉRIFIÉ_CODE` + `HYPOTHÈSE` : **régression Monolog non interceptée** — la migration `addInfo` → `info` dans `AppLogger` ne sera pas couverte par les tests actuels. Un test qui instancie `AppLogger` et vérifie qu'aucune exception n'est levée (voire qu'une entrée est écrite sur un handler en mémoire) est la seule façon d'attraper cette régression avant la production. Preuve : aucune occurrence d'`AppLogger` dans `tests/`.
- `VÉRIFIÉ_CODE` + `HYPOTHÈSE` : **comportement non couvert sur clé manquante** — `VÉRIFIÉ_CODE` : aucun test dans `tests/InvoiceCalculatorTest.php` ne couvre le cas d'une ligne avec une clé absente, et la boucle accède directement à `quantite`/`prixUnitaire` sans garde (`src/InvoiceCalculator.php:21`). `HYPOTHÈSE` : un tel appel pourrait produire un total incorrect sans signal d'erreur explicite — le comportement runtime exact n'a pas été observé à l'exécution.

## Recommandations priorisées

1. **Ajouter un test pour `AppLogger`** — au minimum : vérifier que l'instanciation réussit avec les paramètres par défaut, et que `factureEmise`/`erreurCalcul` s'exécutent sans exception. Un `TestHandler` Monolog (présent dans certaines versions du package — à confirmer selon la version `^1.25` réellement installée) permettrait d'asserter le niveau et le contenu du message enregistré. Fichier à créer : `tests/AppLoggerTest.php`.
2. **Ajouter des tests de cas limites pour `InvoiceCalculator`** — tableau vide, ligne avec clé manquante (tester que le comportement est documenté ou qu'une exception est levée), valeur négative. Fichier : `tests/InvoiceCalculatorTest.php`.
3. **Activer la collecte de couverture dans `phpunit.xml`** — ajouter `<coverage>` avec un rapport HTML ou Clover ; fixer un seuil minimum (ex. 80 % lignes). Fichier : `phpunit.xml`.
4. **Versionner `composer.lock`** — garantit que CI et développeurs utilisent exactement les mêmes versions de Monolog et PHPUnit. Fichier à créer : `composer.lock` (généré par `composer install`).

## Questions ouvertes

- Le comportement de `totalHorsTaxe([])` (retourner `0`) est-il un comportement métier attendu (facture vide = 0 F CFP) ou une erreur à rejeter ?
- L'intégration `InvoiceCalculator` + `AppLogger` doit-elle être testée ici ou dans l'application hôte ?
- Un test d'intégration qui appelle `totalTtc` puis `factureEmise` est-il dans le périmètre du pilote ?
