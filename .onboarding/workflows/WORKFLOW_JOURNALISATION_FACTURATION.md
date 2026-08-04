# WORKFLOW_JOURNALISATION_FACTURATION — Journalisation des événements de facturation

## Classification
- **Type** : technical_flow
- **Sous-type** : journalisation applicative (observabilité)
- **Visibilité** : technical
- **Acteur principal** : application consommatrice de la bibliothèque
- **Acteurs** : code appelant PHP, `App\AppLogger`, `Monolog\Logger`
- **Criticité** : `HYPOTHÈSE` — indéterminée au niveau de ce dépôt. La journalisation serait non bloquante si l'appelant isole les appels dans un `try/catch` ; mais aucun appelant n'est visible dans le dépôt, et `AppLogger` n'implémente lui-même aucun mécanisme d'isolation : ni `new StreamHandler($fichier)` ni `addInfo()`/`addError()` ne sont enveloppés dans un bloc de capture (`src/AppLogger.php:18, 23, 28`). Toute exception Monolog se propagerait à l'appelant.
- **Périmètre** : ce document décrit la **façade de journalisation disponible dans le dépôt** (`AppLogger`) — pas un workflow de facturation observé de bout en bout. Aucun appelant visible ne relie `AppLogger` à `InvoiceCalculator`.
- **Confiance** : medium (bornée à la façade locale `AppLogger` — pas au comportement interne de Monolog ni à une intégration réelle au calcul de facture)
- **Justification** : `src/AppLogger.php` lu intégralement (30 lignes) et dépendance `monolog/monolog ^1.25` vérifiée dans `composer.json` — la façade `AppLogger` est entièrement couverte. La confiance est limitée à `medium` car : aucun test dédié à `AppLogger` n'existe, aucun appelant visible ne prouve l'intégration au calcul de facture, et le comportement effectif (format de sortie, gestion d'erreur du StreamHandler) dépend du fonctionnement interne de Monolog, non observable dans ce dépôt.

## Objectif
Exposer une façade de journalisation autour de Monolog 1.x, avec deux méthodes dont les noms évoquent la facturation : `factureEmise` (niveau INFO, total TTC) et `erreurCalcul` (niveau ERROR, message libre). `AppLogger` configure un `StreamHandler` vers `php://stderr` par défaut (`src/AppLogger.php:15-18`) — destination attendue ; l'écriture effective dépend du comportement interne de Monolog, non observable dans ce dépôt. La classe ne génère aucun effet de bord connu au-delà de la délégation à Monolog. `HYPOTHÈSE` : la destination effective (facturation) est déduite des noms de méthode ; aucun appelant dans le dépôt ne prouve que cette classe est réellement branchée sur `InvoiceCalculator`.

## Acteurs
- **Application consommatrice** : code PHP qui instancie `AppLogger` et appelle ses méthodes au bon moment dans le flux de facturation
- **`App\AppLogger`** (`src/AppLogger.php`) : encapsule `Monolog\Logger` et expose deux méthodes métier
- **`Monolog\Logger` + `Monolog\Handler\StreamHandler`** : moteur de journalisation (`monolog/monolog ^1.25`, `composer.json`)

## Points d'entrée
- `App\AppLogger::factureEmise(int $totalTtc): void` — signale l'émission réussie d'une facture
- `App\AppLogger::erreurCalcul(string $message): void` — signale une erreur lors du calcul
- `App\AppLogger::__construct(string $canal = 'facturation', string $fichier = 'php://stderr')` — configure le canal et la destination à l'instanciation

## Étapes principales

### Chemin A — Facture émise (INFO)
1. L'appelant dispose d'un `$totalTtc` (`int`) et appelle la méthode. `HYPOTHÈSE` : l'origine de ce total (ex. `InvoiceCalculator::totalTtc`) n'est pas établie — aucun appelant n'est visible dans le dépôt.
2. Il appelle `$logger->factureEmise($totalTtc)` (`src/AppLogger.php:21`).
3. `AppLogger` délègue à `$this->logger->addInfo('Facture émise', ['total_ttc' => $totalTtc])` (`src/AppLogger.php:23`).
4. `HYPOTHÈSE` : Monolog est attendu d'écrire une entrée de niveau *INFO* sur le flux configuré (`StreamHandler`). L'écriture effective dépend du comportement interne de Monolog — non observable dans ce dépôt.

### Chemin B — Erreur de calcul (ERROR)
1. L'appelant détecte une erreur lors du calcul et construit un message descriptif.
2. Il appelle `$logger->erreurCalcul($message)` (`src/AppLogger.php:26`).
3. `AppLogger` délègue à `$this->logger->addError('Erreur de calcul', ['detail' => $message])` (`src/AppLogger.php:28`).
4. `HYPOTHÈSE` : Monolog est attendu d'écrire une entrée de niveau *ERROR* sur le flux configuré (`StreamHandler`). L'écriture effective dépend du comportement interne de Monolog — non observable dans ce dépôt.

## Règles métier
- **Deux seuls événements exposés** : `factureEmise` (INFO) et `erreurCalcul` (ERROR). Pas de niveau WARNING, DEBUG ou CRITICAL.
- **Contexte Monolog structuré et figé** : facture émise → clé `total_ttc` (int) ; erreur → clé `detail` (string). Clés non paramétrables (`src/AppLogger.php:23, 28`).
- **Canal par défaut** : `'facturation'` (`src/AppLogger.php:15`).
- **Destination par défaut** : `php://stderr` (`src/AppLogger.php:15`).
- **Pas de formatter configuré** : `StreamHandler` instancié sans appel à `setFormatter` (`src/AppLogger.php:18`). `HYPOTHÈSE` : le format de sortie effectif dépend du comportement par défaut interne de Monolog — non observable dans ce dépôt.
- **API Monolog 1.x** : méthodes `addInfo()` et `addError()` utilisées, explicitement décrites comme appartenant à l'API Monolog 1.x dans le docblock (`src/AppLogger.php:9`). La contrainte `^1.25` dans `composer.json:8` borne la version installée à Monolog 1.x (`VÉRIFIÉ_CODE`).

## Données
- **`$totalTtc`** : `int` en francs CFP — transmis tel quel dans le contexte log, sans transformation
- **`$message`** : `string` libre fourni par l'appelant — aucun format imposé
- **Clés de contexte Monolog** : `total_ttc` (chemin A) et `detail` (chemin B) — figées dans le code

## Intégrations
- **Monolog** (`monolog/monolog ^1.25`, `composer.json`) : bibliothèque de journalisation PHP. `StreamHandler` vers `php://stderr` par défaut. Aucun appel réseau ni base de données.

## Risques
- **HYPOTHÈSE — Incompatibilité potentielle Monolog 2.x+** : le docblock (`src/AppLogger.php:9`) qualifie explicitement `addInfo()`/`addError()` comme appartenant à l'API Monolog 1.x. Le comportement de ces méthodes dans une version 2.x de Monolog n'est pas vérifiable depuis ce dépôt (vendor non installé dans le checkout). La contrainte `^1.25` dans `composer.json:8` borne la version installée et prévient aujourd'hui toute divergence.
- **HYPOTHÈSE — Comportement du StreamHandler en cas d'échec** : `new StreamHandler($fichier)` est appelé sans `try/catch` (`src/AppLogger.php:18`). Le comportement de Monolog en cas d'inaccessibilité du flux (exception, avertissement, autre) est régi par la bibliothèque — non visible dans ce dépôt.
- **Message d'erreur libre** : `erreurCalcul(string $message)` accepte n'importe quelle chaîne sans format imposé. Le code transmet bien une clé de contexte structurée `detail` à Monolog (`src/AppLogger.php:28`), mais le dépôt ne prouve pas comment ce contexte sera sérialisé en sortie ni comment des outils externes (ELK, Loki) pourraient l'exploiter. `HYPOTHÈSE` : exploitabilité du champ `detail` non établie sans vendor installé ni observation d'une sortie réelle.

## Questions ouvertes
- L'appelant est-il censé combiner `InvoiceCalculator` et `AppLogger` lui-même, ou une couche d'orchestration (service, façade) est-elle prévue ?
- Le canal `'facturation'` et la destination `php://stderr` sont-ils les seuls utilisés en pratique, ou les paramètres du constructeur sont-ils effectivement configurés hors du code visible ?
- La migration vers Monolog 2.x (`addInfo → info`, `addError → error`) est-elle planifiée ?

## Preuves
- `src/AppLogger.php` (lu intégralement)
- `composer.json` (dépendance `monolog/monolog: ^1.25` vérifiée)
