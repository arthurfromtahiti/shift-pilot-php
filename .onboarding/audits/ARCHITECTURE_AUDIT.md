# Architecture — Audit

> Confiance : high — les 7 fichiers versionnés ont été lus intégralement ; le périmètre du dépôt est sans ambiguïté.

## Compréhension globale

`shift-pilot-php` est une **bibliothèque PHP pure** : deux classes, aucun contrôleur, aucune route, aucun ORM, aucune couche HTTP. `InvoiceCalculator` porte l'unique logique métier (calcul HT/TTC avec TGC) ; `AppLogger` est un adaptateur technique autour de Monolog 1.x. L'autoload PSR-4 (`App\` → `src/`) et l'absence de framework situent ce dépôt comme un composant destiné à être consommé par une application hôte — jamais exécuté seul.

## Résumé exécutif

L'architecture est **volontairement minimale** et sans dette structurelle grave à son échelle. Les deux classes ont des responsabilités bien séparées — calcul d'un côté, journalisation de l'autre — et aucune n'empiète sur l'autre dans le code visible. Le dépôt ne contient pas de couche d'orchestration qui lierait les deux : l'intégration est entièrement déléguée au consommateur. Cette clarté est une force pour un pilote. Elle devient une fragilité si le projet grossit : sans interface ni injection de dépendance, `AppLogger` est indirectement lié à Monolog et tout remplacement du handler exige de modifier le constructeur. L'ancrage sur Monolog 1.x (`^1.25`, `composer.json:8`) est le principal point d'attention architectural : l'API utilisée (`addInfo`/`addError`) est marquée dans le docblock comme appartenant à Monolog 1.x (`src/AppLogger.php:9`). `HYPOTHÈSE` (connaissance externe au dépôt) : ces méthodes auraient été retirées en Monolog 2.0. Tant que la contrainte `^1.25` tient, il n'y a pas de problème ; si elle est relâchée sans adapter le code, la bibliothèque risque de lever une erreur à l'exécution — l'impact exact dépend de la version installée. Enfin, l'absence de `composer.lock` fait que deux environnements distincts peuvent installer des versions différentes de Monolog dans la plage `^1.25`, ce qui rend les builds non reproductibles.

## Constats détaillés

**Séparation des responsabilités.** `VÉRIFIÉ_CODE` — `InvoiceCalculator` ne connaît pas `AppLogger` et vice-versa (`src/InvoiceCalculator.php:1-32`, `src/AppLogger.php:1-30`). Aucune dépendance croisée n'existe dans le source. La séparation est nette et correcte.

**Couche d'orchestration absente.** `VÉRIFIÉ_CODE` — aucun fichier dans `src/` ne réunit les deux classes (arbre `origin/main` complet = 2 fichiers dans `src/`). L'intégration — instancier les deux objets, appeler `totalTtc` puis `factureEmise` — est entièrement déléguée à l'application hôte. Pour un pilote bibliothèque, c'est cohérent ; pour une évolution vers une API ou un service, cette couche manque.

**Absence d'interfaces et de DI.** `VÉRIFIÉ_CODE` — `AppLogger` instancie directement `new Logger($canal)` et `new StreamHandler($fichier)` dans son constructeur (`src/AppLogger.php:17-18`). Aucune interface PHP n'est définie ni injectée (`composer.json` ne déclare aucune dépendance d'injection). `HYPOTHÈSE` : tester l'`AppLogger` en isolation (remplacer le handler Monolog par un handler en mémoire) exigerait soit de sous-classer la classe, soit de modifier son constructeur — les deux approches sont contraignantes. Si l'application hôte veut substituer un handler différent (base de données, Slack, HTTP), elle devra soit étendre `AppLogger`, soit réécrire son constructeur.

**Ancrage Monolog 1.x.** `VÉRIFIÉ_CODE` — la contrainte `"monolog/monolog": "^1.25"` (`composer.json:8`) et le docblock (`src/AppLogger.php:9`) indiquent explicitement l'API 1.x. `HYPOTHÈSE` (connaissance externe au dépôt, non observable depuis le source) : `addInfo()`/`addError()` ont été retirés en Monolog 2.0 au profit de `info()`/`error()`. La contrainte `^1.25` prévient aujourd'hui toute installation silencieuse d'une version incompatible. Si elle est jamais relâchée sans adaptation du code, la bibliothèque cassera ou pourra casser à l'exécution — le mode exact de panne dépend de la version de Monolog installée et ne peut pas être établi depuis le seul dépôt.

**PHP >= 8.0, pas de fonctionnalités modernes exploitées.** `VÉRIFIÉ_CODE` — `composer.json:6` requiert `"php": ">=8.0"`. Le code n'utilise ni `match`, ni constructor promotion, ni les named arguments — ce n'est pas un défaut (le code est simple et lisible), mais c'est une observation utile si le projet cible un upgrade vers PHP 8.x strict.

**Pas de `composer.lock`.** `VÉRIFIÉ_CODE` — `README.md:13` le mentionne explicitement : *« Le fichier `composer.lock` n'est pas versionné à ce jour. »* Sans ce fichier, `composer install` résoudra les dépendances dans les plages déclarées ; deux exécutions sur des environnements différents (CI, dev, prod) peuvent installer des versions différentes de Monolog entre `1.25` et `< 2.0`. Le comportement reste dans la zone couverte par les tests, mais n'est pas reproductible au bit près.

## Forces

- `VÉRIFIÉ_CODE` : séparation nette calcul / journalisation — aucune dépendance croisée entre les deux classes (`src/InvoiceCalculator.php`, `src/AppLogger.php`).
- `VÉRIFIÉ_CODE` : autoload PSR-4 correctement configuré, namespace `App\` cohérent entre `composer.json`, les deux classes et les tests.
- `VÉRIFIÉ_CODE` : constantes de domaine explicitement nommées (`TGC_STANDARD`, `TGC_REDUIT`) plutôt que des littéraux éparpillés — règles métier localisables en un seul endroit (`src/InvoiceCalculator.php:11-12`).

## Dettes techniques

- `VÉRIFIÉ_CODE` : absence d'interface sur `AppLogger` (`src/AppLogger.php`) — le consommateur ne peut pas substituer une implémentation alternative (handler en mémoire, mock, autre service de log) sans modifier la classe. Dette modérée pour un pilote, bloquante si le projet grossit.
- `VÉRIFIÉ_CODE` : `composer.lock` absent (`README.md:13`) — builds non reproductibles entre environnements.
- `HYPOTHÈSE` : API Monolog 1.x (`addInfo`/`addError`) en fin de vie (`src/AppLogger.php:9`) — dette à traiter avant tout relâchement de la contrainte de version.

## Zones critiques

- **`src/AppLogger.php`** — le constructeur instancie directement Monolog sans interface ; l'API utilisée (`addInfo`/`addError`) est explicitement marquée Monolog 1.x dans le docblock (`src/AppLogger.php:9`). `HYPOTHÈSE` (connaissance externe, non sourcée dans le dépôt) : ces méthodes auraient été retirées en Monolog 2.0. Un senior regarderait ici en premier si le projet devait évoluer ou être testé unitairement.
- **`composer.json:8`** — la contrainte `^1.25` est le seul garde-fou contre une incompatibilité Monolog. Toute PR qui la touche sans adapter `AppLogger.php` est un risque de rupture à l'exécution.

## Risques

- `HYPOTHÈSE` : **risque de rupture à la montée de version Monolog** — si `composer.json:8` est modifié de `^1.25` à `^2.0` ou `*` sans adapter `src/AppLogger.php:23,28`, les appels `addInfo()`/`addError()` lèveront une erreur à l'exécution. Impact : la journalisation cesse ; selon le contexte d'appel, l'exception peut remonter à l'application hôte. Preuve : docblock `src/AppLogger.php:9` + contrainte `composer.json:8`.
- `VÉRIFIÉ_CODE` : **absence de `composer.lock`** — une régression introduite par une mise à jour de Monolog dans la plage `^1.25` ne serait détectée que si les tests sont lancés dans l'environnement mis à jour. Avec seulement 3 tests (aucun sur `AppLogger`), la fenêtre de détection est étroite.

## Recommandations priorisées

1. **Ajouter un `composer.lock`** — exécuter `composer install` dans un environnement de référence et versionner le fichier. Garantit la reproductibilité des builds et des exécutions de tests. Fichier concerné : `composer.json`, à la racine.
2. **Migrer l'API Monolog vers 2.x/3.x** (`addInfo` → `info`, `addError` → `error`) et mettre à jour la contrainte dans `composer.json:8` — avant toute autre évolution de la bibliothèque. Fichier concerné : `src/AppLogger.php:23,28` + `composer.json:8`.
3. **Extraire une interface `LoggerInterface`** (ou réutiliser `Psr\Log\LoggerInterface`) et injecter le logger dans le constructeur d'`AppLogger` — permettrait les tests en isolation et la substitution d'implémentation. Fichier concerné : `src/AppLogger.php`.

## Questions ouvertes

- Le pilote est-il destiné à rester une bibliothèque pure, ou à évoluer vers un service avec couche HTTP (contrôleur, route) ? La réponse change radicalement les recommandations d'architecture.
- L'intégration `InvoiceCalculator` + `AppLogger` est-elle attendue dans ce dépôt ou dans l'application hôte ?
- PHP 8.0 minimum (`composer.json:6`) : est-ce volontaire (compatibilité large) ou un héritage ?
