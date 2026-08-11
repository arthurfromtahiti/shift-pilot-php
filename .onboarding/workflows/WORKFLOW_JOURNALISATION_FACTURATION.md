# WORKFLOW_JOURNALISATION_FACTURATION — Journalisation des événements de facturation

> **Réconciliation** — run SHIAAAAAAAAAAAAAAAAAAAAAAAA-498 (2026-08-08, SHA `6d4f877`).
> La version précédente documentait Monolog 1.x (`addInfo`/`addError`, `^1.25`). Depuis, le projet a migré vers Monolog 3.x (`info`/`error`, `^3.0`, commit `c812c5d` CLA-182). Les sections concernées ont été mises à jour ; voir *Réconciliation* en fin de document.

## Classification
- **Type** : technical_flow
- **Sous-type** : journalisation applicative (observabilité)
- **Visibilité** : technical
- **Acteur principal** : application consommatrice de la bibliothèque
- **Acteurs** : code appelant PHP, `App\AppLogger`, `Monolog\Logger`
- **Criticité** : indéterminée — `AppLogger` est un composant disponible mais **aucun appelant n'est câblé dans ce dépôt** (`VÉRIFIÉ_CODE` — `rg "AppLogger|factureEmise|erreurCalcul" src/ tests/ README.md` ne remonte que `src/AppLogger.php`). Toute exception Monolog se propagerait à l'appelant, car aucune isolation (`try/catch`) n'est présente dans `AppLogger` lui-même (`src/AppLogger.php:18,23,28`).
- **Périmètre** : ce document décrit la **façade de journalisation disponible** (`AppLogger`) — pas un flux de log observé de bout en bout. Aucun appelant visible ne relie `AppLogger` à `InvoiceCalculator`.
- **Confiance** : medium (bornée à la façade locale `AppLogger` — pas au comportement interne de Monolog ni à une intégration réelle au calcul de facture)
- **Justification** : `src/AppLogger.php` lu intégralement (30 lignes) ; `composer.json:8` vérifié (`monolog/monolog: ^3.0`). La confiance est limitée à `medium` car : aucun test dédié à `AppLogger` n'existe dans le dépôt, aucun appelant visible ne prouve l'intégration au calcul de facture, et le comportement effectif (format de sortie, gestion d'erreur du `StreamHandler`) dépend du fonctionnement interne de Monolog, non observable sans le vendor.

## Objectif
Exposer une façade de journalisation autour de Monolog 3.x, avec deux méthodes nommées d'après la facturation : `factureEmise` (niveau INFO, total TTC) et `erreurCalcul` (niveau ERROR, message libre). `AppLogger` configure un `StreamHandler` vers `php://stderr` par défaut (`src/AppLogger.php:15-18`) — destination attendue ; l'écriture effective dépend du comportement interne de Monolog. La classe ne génère aucun effet de bord connu au-delà de la délégation à Monolog. **`HYPOTHÈSE`** : la destination effective (facturation) est déduite des noms de méthode ; aucun appelant dans le dépôt ne prouve que cette classe est réellement branchée sur `InvoiceCalculator`.

## Acteurs
- **Application consommatrice** : code PHP qui instancie `AppLogger` et appelle ses méthodes au bon moment dans le flux de facturation
- **`App\AppLogger`** (`src/AppLogger.php`) : encapsule `Monolog\Logger` et expose deux méthodes métier
- **`Monolog\Logger` + `Monolog\Handler\StreamHandler`** : moteur de journalisation (`monolog/monolog ^3.0`, `composer.json:8`)

## Points d'entrée
- `App\AppLogger::__construct(string $canal = 'facturation', string $fichier = 'php://stderr')` — configure le canal et la destination à l'instanciation (`src/AppLogger.php:15`)
- `App\AppLogger::factureEmise(int $totalTtc): void` — journalise l'événement que l'appelant lui présente comme une facture émise (`src/AppLogger.php:21`)
- `App\AppLogger::erreurCalcul(string $message): void` — signale une erreur lors du calcul (`src/AppLogger.php:26`)

## Étapes principales

### Chemin A — Facture émise (INFO)
1. L'appelant dispose d'un `$totalTtc` (`int`) et souhaite l'enregistrer. **`HYPOTHÈSE`** : l'origine de ce total (ex. `InvoiceCalculator::totalTtc`) n'est pas établie — aucun appelant n'est visible dans le dépôt.
2. Il appelle `$logger->factureEmise($totalTtc)` (`src/AppLogger.php:21`).
3. `AppLogger` délègue à `$this->logger->info('Facture émise', ['total_ttc' => $totalTtc])` (`src/AppLogger.php:23`).
4. **`HYPOTHÈSE`** : Monolog est attendu d'écrire une entrée de niveau *INFO* sur le flux configuré (`StreamHandler`). L'écriture effective dépend du comportement interne de Monolog — non observable sans le vendor.

### Chemin B — Erreur de calcul (ERROR)
1. L'appelant détecte une erreur lors du calcul et construit un message descriptif.
2. Il appelle `$logger->erreurCalcul($message)` (`src/AppLogger.php:26`).
3. `AppLogger` délègue à `$this->logger->error('Erreur de calcul', ['detail' => $message])` (`src/AppLogger.php:28`).
4. **`HYPOTHÈSE`** : Monolog est attendu d'écrire une entrée de niveau *ERROR* sur le flux configuré (`StreamHandler`). L'écriture effective dépend du comportement interne de Monolog — non observable sans le vendor.

## Règles métier
- **Deux seuls événements exposés** : `factureEmise` (INFO) et `erreurCalcul` (ERROR). Pas de niveau WARNING, DEBUG ou CRITICAL.
- **Contexte Monolog structuré et figé** : facture émise → clé `total_ttc` (int) ; erreur → clé `detail` (string). Clés non paramétrables (`src/AppLogger.php:23,28`).
- **Canal par défaut** : `'facturation'` (`src/AppLogger.php:15`).
- **Destination par défaut** : `php://stderr` (`src/AppLogger.php:15`).
- **API Monolog 3.x** : méthodes `info()` et `error()` utilisées (`src/AppLogger.php:23,28`), conformes à l'API Monolog 3.x déclarée dans le docblock (`src/AppLogger.php:9`). Contrainte `^3.0` dans `composer.json:8`.
- **Pas de formatter configuré** : `StreamHandler` instancié sans appel à `setFormatter` (`src/AppLogger.php:18`). **`HYPOTHÈSE`** : le format de sortie effectif dépend du comportement par défaut interne de Monolog — non observable sans le vendor.

## Données
- **`$totalTtc`** : `int` en francs CFP — transmis tel quel dans le contexte log, sans transformation
- **`$message`** : `string` libre fourni par l'appelant — aucun format imposé
- **Clés de contexte Monolog** : `total_ttc` (chemin A) et `detail` (chemin B) — figées dans le code

## Intégrations
- **Monolog** (`monolog/monolog ^3.0`, `composer.json:8`) : bibliothèque de journalisation PHP. `StreamHandler` vers `php://stderr` par défaut. Aucun appel réseau ni base de données.

## Risques
- **Comportement du StreamHandler en cas d'échec** : `new StreamHandler($fichier)` est appelé sans `try/catch` (`src/AppLogger.php:18`). **`HYPOTHÈSE`** : le comportement de Monolog en cas d'inaccessibilité du flux (exception, avertissement, autre) est régi par la bibliothèque — non visible sans le vendor.
- **Message d'erreur libre** : `erreurCalcul(string $message)` accepte n'importe quelle chaîne sans format imposé. Le code transmet une clé de contexte structurée `detail` à Monolog (`src/AppLogger.php:28`), mais le dépôt ne prouve pas comment ce contexte sera sérialisé en sortie ni comment des outils externes (ELK, Loki) pourraient l'exploiter. **`HYPOTHÈSE`** : exploitabilité du champ `detail` non établie sans vendor installé ni observation d'une sortie réelle.
- **Aucun appelant câblé** : `AppLogger` est un composant disponible mais non utilisé dans le dépôt. Si l'intégration avec `InvoiceCalculator` est attendue, elle reste entièrement à la charge de l'appelant externe — aucun raccordement automatique n'existe.

## Questions ouvertes
- L'appelant est-il censé combiner `InvoiceCalculator` et `AppLogger` lui-même, ou une couche d'orchestration (service, façade) est-elle prévue ?
- Le canal `'facturation'` et la destination `php://stderr` sont-ils les seuls utilisés en pratique, ou les paramètres du constructeur sont-ils effectivement configurés hors du code visible ?
- Aucun test unitaire ne couvre `AppLogger` — est-ce intentionnel (composant trivial) ou un oubli ?

## Preuves
- `src/AppLogger.php` (lu intégralement, 30 lignes)
- `composer.json` (dépendance `monolog/monolog: ^3.0` vérifiée, ligne 8)

## Réconciliation (run SHIAAAAAAAAAAAAAAAAAAAAAAAA-498, 2026-08-08, SHA `6d4f877`)

La version précédente documentait Monolog 1.x. Migration vers Monolog 3.x effectuée au commit `c812c5d` (CLA-182). Dérives corrigées :

| Section | Dérive précédente | Correction |
|---|---|---|
| Acteurs / Intégrations | `monolog/monolog ^1.25` | `monolog/monolog ^3.0` (`composer.json:8`) |
| Étapes A et B | `addInfo()` / `addError()` | `info()` / `error()` (`src/AppLogger.php:23,28`) |
| Règles métier | « API Monolog 1.x : méthodes `addInfo()`/`addError()` » | « API Monolog 3.x : méthodes `info()`/`error()` » |
| Risques | « Incompatibilité potentielle Monolog 2.x+ » | Supprimé — non pertinent, la version est sur Monolog 3.x avec contrainte `^3.0` |
| Questions ouvertes | « La migration vers Monolog 2.x est-elle planifiée ? » | Supprimée — migration déjà effectuée vers Monolog 3.x |
