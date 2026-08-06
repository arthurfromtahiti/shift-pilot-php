# Points chauds du code — Audit

> Confiance : high — les deux fichiers source sont courts (30-32 lignes) et ont été lus intégralement. Le risque principal n'est pas la complexité mais l'utilisation d'une API obsolète et l'absence de gardes d'entrée sur le seul chemin métier critique.

## Compréhension globale

Avec seulement deux fichiers source et 62 lignes de code total, le dépôt n'a pas de « hot spot » au sens de la complexité cyclomatique ou de la profondeur d'appel. Les points chauds ici sont définis par leur **risque métier et leur fragilité technique** : `InvoiceCalculator` parce qu'il concentre l'unique logique métier du projet sans défense d'entrée, et `AppLogger` parce qu'il repose sur une API Monolog marquée obsolète. Un développeur qui touche l'un ou l'autre sans connaître ces contraintes peut introduire une régression invisible.

## Résumé exécutif

`src/InvoiceCalculator.php` (47 lignes) est le seul fichier métier critique du dépôt. Son chemin nominal est couvert par 3 tests ; ses cas limites (clés manquantes, valeurs négatives, tableau vide) ne le sont pas. La boucle d'agrégation (`src/InvoiceCalculator.php:21-26`) est le point d'entrée de toute erreur de calcul silencieuse : six lignes de code, aucune garde. `src/AppLogger.php` (30 lignes) est un adaptateur technique correct dans son périmètre, mais son constructeur instancie Monolog sans `try/catch` et ses deux méthodes publiques utilisent une API (`addInfo`, `addError`) que le docblock qualifie d'API Monolog 1.x (`src/AppLogger.php:9`) — `HYPOTHÈSE` (connaissance externe au dépôt) : ces méthodes auraient été retirées en Monolog 2.0. Aucun test ne couvre `AppLogger`. Ces deux fichiers sont petits mais leur modification sans vigilance est un vecteur de régression directe sur la fonctionnalité ou sur l'observabilité de la bibliothèque.

## Constats détaillés

**`src/InvoiceCalculator.php` — boucle sans défense (lignes 21-26).** `VÉRIFIÉ_CODE` — la boucle centrale `foreach ($lignes as $ligne) { $total += $ligne['quantite'] * $ligne['prixUnitaire']; }` n'a ni vérification d'existence de clé (`isset`), ni vérification de type (`is_int`), ni rejet de valeur négative. C'est la seule logique métier du projet et elle s'exécute sans filet. `HYPOTHÈSE` : un appelant qui passe une ligne avec une clé nommée `qty` au lieu de `quantite` peut obtenir un résultat faussé pour cette ligne sans erreur fonctionnelle explicite — le comportement exact (valeur produite, présence ou absence d'un warning PHP) dépend de la version PHP et du contexte d'exécution, et n'a pas été observé.

**`src/InvoiceCalculator.php` — arrondi explicite dans les deux méthodes (lignes 27, 45).** `VÉRIFIÉ_CODE` — `(int) round($total)` dans `totalHorsTaxe()` (ligne 27) et `(int) round($ht * (1 + $taux))` dans `totalTtc()` (ligne 45) appliquent explicitement `round()` sans second argument : le mode d'arrondi n'est pas explicité dans le code. `HYPOTHÈSE` (contexte langage) : PHP appliquerait `PHP_ROUND_HALF_UP` par défaut — ce n'est pas une preuve issue du dépôt. Le mode d'arrondi est-il délibérément aligné sur les règles de la réglementation CFP ? Ce n'est pas documenté. Si la règle fiscale exigeait `PHP_ROUND_HALF_DOWN` ou `PHP_ROUND_HALF_EVEN`, le code serait incorrect pour certaines valeurs de demi-franc — sans test couvrant ces valeurs, la dérive serait silencieuse.

**`src/AppLogger.php` — API Monolog 1.x explicitement marquée obsolète (lignes 23, 28).** `VÉRIFIÉ_CODE` — le docblock de la classe (`src/AppLogger.php:9`) qualifie lui-même les méthodes `addInfo()`/`addError()` comme appartenant à l'API Monolog 1.x. `composer.json:8` contraint la version à `^1.25`, ce qui empêche actuellement l'installation d'une version 2.x incompatible. `HYPOTHÈSE` : si cette contrainte est levée (mise à jour de `composer.json`), les deux appels aux lignes 23 et 28 produisent une erreur à l'exécution. Ce risque est codifié dans le dépôt lui-même (docblock d'avertissement) sans que l'auteur ait documenté le chemin de migration.

**`src/AppLogger.php` — constructeur sans gestion d'erreur (ligne 18).** `VÉRIFIÉ_CODE` — `$this->logger->pushHandler(new StreamHandler($fichier))` est la seule ligne d'initialisation, sans `try/catch` observable (`src/AppLogger.php:15-18`). `HYPOTHÈSE` : si `$fichier` est un chemin invalide ou inaccessible, Monolog peut lever une exception qui remonterait sans interception jusqu'à l'application hôte — le comportement exact selon la valeur passée n'a pas été observé à l'exécution.

**Couplage temporel entre `InvoiceCalculator` et `AppLogger`.** `HYPOTHÈSE` — les noms de méthodes (`factureEmise`, `erreurCalcul`) suggèrent que `AppLogger` est destiné à être appelé juste après `totalTtc`. Pourtant, aucun code dans le dépôt n'illustre ni n'enforces cet ordre. Si l'application hôte appelle `erreurCalcul` avant toute erreur réelle, ou `factureEmise` avec un total non issu d'`InvoiceCalculator`, la bibliothèque l'accepte sans signal.

## Forces

- `VÉRIFIÉ_CODE` : les deux fichiers sont courts et focalisés — aucun n'excède 32 lignes, aucun ne mélange des responsabilités multiples.
- `VÉRIFIÉ_CODE` : `InvoiceCalculator` délègue entièrement à `totalHorsTaxe` depuis `totalTtc` — pas de duplication de la logique d'agrégation (`src/InvoiceCalculator.php:28`).
- `VÉRIFIÉ_CODE` : le docblock `src/AppLogger.php:9` avertit explicitement que l'API utilisée est Monolog 1.x — un développeur attentif est prévenu.

## Dettes techniques

- `VÉRIFIÉ_CODE` : boucle d'agrégation sans garde d'entrée — `src/InvoiceCalculator.php:21-26`.
- `VÉRIFIÉ_CODE` : API Monolog 1.x (`addInfo`/`addError`) sans chemin de migration documenté — `src/AppLogger.php:23,28`.
- `VÉRIFIÉ_CODE` : constructeur `AppLogger` sans `try/catch` sur `StreamHandler` — `src/AppLogger.php:15-18`.

## Zones critiques

- **`src/InvoiceCalculator.php:21-26`** — la boucle d'agrégation est le cœur métier du projet. Un senior la regarderait en premier avant d'ajouter une règle (remise, avoir, taux par ligne) car elle ne tolère aucune données malformées sans erreur silencieuse.
- **`src/AppLogger.php:9,23,28`** — le trio docblock + deux appels `addInfo`/`addError` est le seul endroit du dépôt qui dépend explicitement d'une API obsolète. Un senior qui devrait upgrader Monolog regarderait ici en premier.

## Risques

- `VÉRIFIÉ_CODE` : **absence de garde sur les clés** (`src/InvoiceCalculator.php:21`) — la boucle accède directement à `quantite` et `prixUnitaire` sans `isset()`. `HYPOTHÈSE` : une clé manquante peut conduire à un total faussé sans exception fonctionnelle explicite — le comportement runtime exact (valeur produite, nature du signal d'erreur) n'a pas été observé.
- `HYPOTHÈSE` : **rupture à la montée de version Monolog** — contrainte `^1.25` levée sans adapter le code → `addInfo()`/`addError()` inexistantes en Monolog 2.x → exception à l'exécution. Fichier : `src/AppLogger.php:23,28` + `composer.json:8`.
- `HYPOTHÈSE` : **mode d'arrondi non documenté** — `round()` sans mode explicite pour des montants en CFP ; si la règle fiscale diffère de `PHP_ROUND_HALF_UP`, le résultat est incorrect pour certaines valeurs limites. Fichier : `src/InvoiceCalculator.php:27,45`.

## Recommandations priorisées

1. **Ajouter des gardes d'entrée dans `totalHorsTaxe`** — `isset($ligne['quantite'], $ligne['prixUnitaire'])` avec `throw new \InvalidArgumentException(...)` explicite. Fichier : `src/InvoiceCalculator.php:21-26`.
2. **Migrer `addInfo`/`addError` vers `info`/`error`** (Monolog 2.x/3.x) et mettre à jour `composer.json:8` en parallèle. Fichier : `src/AppLogger.php:23,28` + `composer.json:8`.
3. **Documenter le mode d'arrondi** — soit commenter l'appel `round()` avec la référence réglementaire CFP, soit passer `PHP_ROUND_HALF_UP` explicitement pour signaler l'intention. Fichier : `src/InvoiceCalculator.php:27,45`.

## Questions ouvertes

- Le mode d'arrondi par défaut (`PHP_ROUND_HALF_UP`) est-il aligné sur la réglementation CFP ou choisi par commodité ?
- Un chemin de migration vers Monolog 2.x est-il planifié, ou la contrainte `^1.25` doit-elle rester indéfiniment ?
- Existe-t-il un exemple d'utilisation prévu (README de l'application hôte, README complémentaire) qui montrerait l'intégration des deux classes ? Ce serait la base naturelle d'un test d'intégration.
