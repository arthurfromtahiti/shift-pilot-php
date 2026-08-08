# Carte des domaines — shift-pilot-php

> **Confiance globale : high** sur chacun des 2 domaines identifiés, mais **couverture fonctionnelle très étroite** — voir *Incertitudes*.
> Dépôt analysé au SHA `6d4f877887a320c6669e4493bbdf7dc979cb9e80` (`HEAD` = `origin/main`, branche par défaut `main`, `VÉRIFIÉ_CODE` via `git remote show origin`).
> Produite en **réconciliation** (socle-onboarding §2ter) par le run **SHIAAAAAAAAAAAAAAAAAAAAAAAA-496** (Découverte de domaines, 2026-08-08), en **lecture seule du code**. Aucun accès base fourni à ce stade ; PHP indisponible dans l'environnement (`php: command not found`, aucun `vendor/`) → aucun statut `OBSERVÉ`, toutes les affirmations sont `VÉRIFIÉ_CODE` (lues `fichier:ligne`) ou `HYPOTHÈSE`.
> Un `.onboarding/` **existe déjà sur le distant** (`origin/main`, 27 fichiers versionnés). La version précédente de cette carte, écrite au SHA `5f5c8ee` (runs CLA-171/CLA-176), avait **dérivé** : le code a changé quatre fois depuis (voir *Réconciliation* en fin de document). Chaque affirmation a été **re-vérifiée fichier par fichier sur le code courant** au SHA `6d4f877`.

## Nature du projet

Bibliothèque PHP (`type: project`, autoload PSR-4 `App\` → `src/`) **sans couche HTTP** : ni contrôleur, ni route, ni framework web, ni ORM (`VÉRIFIÉ_CODE` — l'arbre `origin/main` ne contient sous `src/` que `AppLogger.php` et `InvoiceCalculator.php`, plus `tests/InvoiceCalculatorTest.php`, et à la racine `README.md`, `PROJECT.md`, `composer.json`, `composer.lock`, `phpunit.xml`, `.gitignore`). Le cœur métier est le **calcul de factures avec la TGC** (taxe générale sur la consommation, Polynésie française), montants en **francs CFP** entiers. Une classe de **journalisation** (Monolog **3.x**) fournit une **API de log dédiée** (méthodes « facture émise » / « erreur de calcul ») ; toutefois **aucun appel depuis `InvoiceCalculator` n'est prouvé** — `src/InvoiceCalculator.php` n'importe ni n'instancie `AppLogger` (`VÉRIFIÉ_CODE`). C'est donc un **composant disponible**, et non une journalisation effectivement câblée sur le calcul. Dépôt volontairement minimal, présenté comme « pilote » (`README.md` ; `composer.json` : *« Pilote de test SHIFT/Paperclip — facturation TGC »*).

## Domaines

### Facturation TGC (`facturation-tgc`)
- **Catégorie** : métier
- **Priorité** : cœur — sans ce calcul, le logiciel n'a pas de raison d'être.
- **Confiance** : high
- **Description** : calcul du total **hors taxe** (`totalHorsTaxe`) puis **toutes taxes comprises** (`totalTtc`) d'une facture composée de lignes. La TGC s'applique à deux taux de domaine : **standard 16 %** (`TGC_STANDARD = 0.16`, `src/InvoiceCalculator.php:11`) et **réduit 5 %** (`TGC_REDUIT = 0.05`, `src/InvoiceCalculator.php:12`, ex. « produit première nécessité »). **Le taux est porté ligne par ligne** : chaque ligne peut fournir sa propre clé `taux` ; à défaut la ligne prend `TGC_STANDARD` (`$taux = $ligne['taux'] ?? self::TGC_STANDARD`, `src/InvoiceCalculator.php:48`). Une même facture peut donc **mélanger des taux** (taux mixte, CLA-250). Montants en **francs CFP** entiers ; les totaux passent par `round()` puis cast `(int)` (`src/InvoiceCalculator.php:28-32,51-55`).
- **Robustesse (deux garde-fous `VÉRIFIÉ_CODE`)** :
  - **Validation d'entrée** : chaque ligne doit contenir `quantite` **et** `prixUnitaire`, sinon `\InvalidArgumentException` (`src/InvoiceCalculator.php:23-25` pour HT, `45-47` pour TTC ; CLA-293/CLA-280).
  - **Débordement** : si le total arrondi dépasse `PHP_INT_MAX` ou est sous `PHP_INT_MIN`, `\OverflowException` (`src/InvoiceCalculator.php:29-31` pour HT, `52-54` pour TTC ; SHIAAAAAAAAAAAAAAAAAAAAAAAA-426).
- **Entités** : aucune entité ORM. La **ligne de facture** est un tableau associatif `{label: string, quantite: int, prixUnitaire: int, taux?: float}` (docblocks `src/InvoiceCalculator.php:15,36`, `VÉRIFIÉ_CODE`). Constantes de domaine `TGC_STANDARD`, `TGC_REDUIT` (`src/InvoiceCalculator.php:11-12`).
- **Routes / points d'entrée** : **aucune route** — appelé comme bibliothèque via les méthodes publiques `totalHorsTaxe(array $lignes): int` (`src/InvoiceCalculator.php:19`) et `totalTtc(array $lignes): int` (`src/InvoiceCalculator.php:41`). Aucun contrôleur ni route localisé malgré parcours complet de l'arbre. **Note de dérive** : la signature `totalTtc` n'a **plus** le paramètre `bool $tauxReduit` décrit dans la version CLA-171 de cette carte — il a été remplacé par le taux par ligne (CLA-250).
- **Indices de rattachement** : classe `InvoiceCalculator` ; méthodes `totalHorsTaxe`, `totalTtc` ; constantes `TGC_*` ; clé de ligne `taux` ; termes `facture`, `TGC`, `taux réduit`, `hors taxe`, `TTC` ; chemin `src/InvoiceCalculator.php`.
- **Types de workflows attendus** : calcul HT → TTC d'une facture ; taux par ligne (standard, réduit, mixte) ; agrégation des lignes ; arrondi monétaire ; rejet des lignes malformées ; protection contre le débordement d'entier.
- **Preuves** : `src/InvoiceCalculator.php` (lu intégralement, 57 lignes) ; `tests/InvoiceCalculatorTest.php` (lu intégralement, 12 cas — voir *Réconciliation*).
- **Dépend de la base** : non établi côté code. Sur les 3 signaux du §6, **les 2 évaluables sur le code sont absents** (aucune entité étendue par un champ layout/blocks/config ; aucun renderer ni décodage récursif de structure) ; **le signal schéma/base reste non évalué** — aucun schéma ni accès base n'a été fourni ni ouvert. À distinguer d'une absence établie : c'est « aucun signal observable dans le code », pas « base auditée et sans dépendance ».

### Journalisation applicative (`journalisation-applicative`)
- **Catégorie** : technique (transverse)
- **Priorité** : support
- **Confiance** : high (code lu intégralement) — périmètre étroit.
- **Description** : émission de journaux applicatifs via **Monolog 3.x**. Deux événements sont exposés en dur : `factureEmise(int $totalTtc)` (niveau *info*, message `'Facture émise'`, contexte `total_ttc`) et `erreurCalcul(string $message)` (niveau *error*, message `'Erreur de calcul'`, contexte `detail`). Canal par défaut `facturation`, sortie par défaut `php://stderr`, tous deux paramétrables au constructeur. **Aucun appelant de cette API n'est présent dans le dépôt** : `InvoiceCalculator` ne l'utilise pas et aucun autre fichier ne l'instancie (`VÉRIFIÉ_CODE` — `rg "AppLogger|factureEmise|erreurCalcul"` ne remonte que `src/AppLogger.php`). `AppLogger` est donc un **composant/API de journalisation disponible**, pas un flux de log effectivement câblé au calcul.
- **Entités** : aucune. Classe `AppLogger` encapsulant `Monolog\Logger` + `StreamHandler` (`src/AppLogger.php:13,17-18`).
- **Routes / points d'entrée** : aucune route — bibliothèque. Points d'entrée : `__construct(string $canal = 'facturation', string $fichier = 'php://stderr')` (`src/AppLogger.php:15`), `factureEmise(int $totalTtc): void` (`src/AppLogger.php:21`), `erreurCalcul(string $message): void` (`src/AppLogger.php:26`).
- **Indices de rattachement** : classe `AppLogger` ; `Monolog\Logger`, `StreamHandler`, `pushHandler` ; méthodes `info`, `error` ; chemin `src/AppLogger.php`.
- **Types de workflows attendus** (usages **potentiels** de l'API — aucun appelant réel dans le dépôt) : journalisation d'une facture émise ; journalisation d'une erreur de calcul ; configuration du canal/handler de sortie.
- **Preuves** : `src/AppLogger.php` (lu intégralement, 30 lignes) ; dépendance `monolog/monolog: ^3.0` (`composer.json:8`), résolue à **`3.10.0`** dans `composer.lock` (`VÉRIFIÉ_CODE`).
- **Dépend de la base** : non.
- **Résolution d'une ancienne `HYPOTHÈSE`** (ne fait pas un domaine) : la version CLA-171 de cette carte portait une `HYPOTHÈSE` sur le retrait de l'API Monolog 1.x (`addInfo`/`addError`) en 2.0. **Cette question est close par le code** : le projet a migré en Monolog 3.x (commit `c812c5d`, CLA-182), `AppLogger` utilise désormais `info()`/`error()` (`src/AppLogger.php:23,28`), le docblock l'indique explicitement (`src/AppLogger.php:9` — *« API Monolog 3.x (info/error) »*), et `composer.json:8` borne à `^3.0`. Plus aucune référence à l'API 1.x dans le code courant.

## Incertitudes

- **Seulement 2 domaines, sous le plancher usuel de 4** (méthode §5). Ce n'est **pas** une fusion abusive : `src/` ne contient que 2 classes (`VÉRIFIÉ_CODE` sur l'arbre `origin/main`). Découper « calcul HT » et « calcul TTC » en domaines distincts confondrait domaine et module — refusé. La rareté vient de la matière, pas de l'analyse (socle : *matière pauvre → livrable court et honnête*).
- **Aucune couche HTTP / persistance / entité ORM.** À confirmer par le board : ce périmètre bibliothèque est-il la cible du projet, ou un socle destiné à grossir (contrôleurs, base, front) ? La réponse conditionne ce que l'Analyste de workflows pourra attendre comme points d'entrée. `PROJECT.md` qualifie le runtime et le canal d'écriture de `na_pilot` (« pilote de test sans serveur de production réel », confirmé interaction c8e9e753) — cohérent avec un pilote.
- **Détection « contenu piloté par la base » partielle** : sans accès base ni schéma fourni, seuls 2 des 3 signaux du §6 ont pu être évalués (entité étendue, code exécutable) — aucun trouvé. Le signal *schéma* reste **non évalué** (pas d'accès base/schéma). À ce stade : aucun signal de dépendance base **observable dans le code**, ce qui n'équivaut pas à une base auditée sans dépendance.
- **Modèle monétaire** : prix et totaux en **entiers** (pas de sous-unité), devise francs CFP — cohérent avec les assertions de test. Le calcul TTC agrège en `float` puis arrondit une seule fois au total (`round()` sur le cumul, pas ligne à ligne), ce qui peut différer d'un arrondi par ligne sur des factures à taux mixte : à valider comme règle métier stable par l'aval (audit/CDC).
- **Impossibilité d'exécuter les tests dans cet environnement** (PHP absent) : la conformité fonctionnelle repose sur la lecture du code et des assertions, pas sur un run vert `OBSERVÉ`. Un environnement PHP 8.1+ permettrait de confirmer par exécution.

## Réconciliation (run SHIAAAAAAAAAAAAAAAAAAAAAAAA-496, 2026-08-08, SHA `6d4f877`)

La version précédente de cette carte a été écrite au SHA `5f5c8ee` (runs CLA-171/CLA-176). Depuis, le code a **réellement dérivé** — le `.onboarding/` a été « remis en cohérence » une fois (commit `af3271e`, SHIAAAAAAAAAAAAAAAAAAAAAAAA-403), mais cette passe n'a corrigé que des **numéros de ligne** et a laissé intactes des affirmations devenues fausses (Monolog 1.x `addInfo`/`addError`, `monolog/monolog: ^1.25`). Historique `git log -- src/ composer.json` :

| Commit | Changement | Impact sur la carte |
|---|---|---|
| `c812c5d` (CLA-182) | monter `monolog ^1.25 → ^3.0`, adapter l'API (`info`/`error`) | **Dérive corrigée** : domaine *Journalisation* réécrit en Monolog 3.x ; ancienne `HYPOTHÈSE` retrait 2.0 close par le code |
| `07313b6` (CLA-250) | taux TGC **par ligne** dans `totalTtc` | **Dérive corrigée** : signature `totalTtc(array $lignes)` (plus de `bool $tauxReduit`), taux via clé `taux`, taux mixte possible |
| `401546f` (CLA-293) + `b53b03e`/`3935220` (CLA-280) | valider `quantite`/`prixUnitaire`, `@throws` PHPDoc | **Dérive corrigée** : garde-fou `InvalidArgumentException` ajouté au domaine Facturation |
| `d938945` (SHIAAAAAAAAAAAAAAAAAAAAAAAA-394) | `(int) round()` au retour de `totalHorsTaxe()` | Aligné HT sur TTC ; déjà partiellement pris en `af3271e` |
| `e5a7644` (SHIAAAAAAAAAAAAAAAAAAAAAAAA-426) | `OverflowException` sur dépassement `PHP_INT_MAX`/`PHP_INT_MIN` | **Dérive corrigée** : garde-fou `OverflowException` ajouté au domaine Facturation |
| `360b31e` (CLA-321) | phpunit `^9.6 → ^10.5` | Sans impact domaine (outillage de test) |

Contrôles `VÉRIFIÉ_CODE` du présent run (relecture ligne par ligne du code au SHA `6d4f877`, non de mémoire) :

| Claim de la carte (version courante) | Source vérifiée | Verdict |
|---|---|---|
| 2 classes seules (`InvoiceCalculator`, `AppLogger`) | `git ls-tree -r origin/main` ; `src/` = ces 2 classes | ✅ exact |
| `TGC_STANDARD = 0.16`, `TGC_REDUIT = 0.05` | `src/InvoiceCalculator.php:11-12` | ✅ exact |
| Ligne = `{label, quantite, prixUnitaire, taux?}`, francs CFP entiers | docblocks `src/InvoiceCalculator.php:15,36` | ✅ exact |
| `totalTtc(array $lignes)` sans `$tauxReduit` ; taux par ligne défaut `TGC_STANDARD` | `src/InvoiceCalculator.php:41,48` | ✅ exact |
| `InvalidArgumentException` si `quantite`/`prixUnitaire` absents (HT et TTC) | `src/InvoiceCalculator.php:23-25,45-47` | ✅ exact |
| `OverflowException` si total hors bornes `PHP_INT_MAX`/`MIN` (HT et TTC) | `src/InvoiceCalculator.php:29-31,52-54` | ✅ exact |
| Journalisation **Monolog 3.x** `info`/`error`, canal `facturation`, `php://stderr` | `src/AppLogger.php:9,15,23,28` | ✅ exact |
| `factureEmise(int)` info `total_ttc` / `erreurCalcul(string)` error `detail` | `src/AppLogger.php:21-28` | ✅ exact |
| `monolog/monolog: ^3.0`, résolu `3.10.0` | `composer.json:8` ; `composer.lock` | ✅ exact |
| Tests : 12 cas (25000 / 11600 / 10500 / mixte 22100 / défaut 11600 / 4× InvalidArgument / 2× Overflow) | `tests/InvoiceCalculatorTest.php:17,24,31,44,51,54-101` | ✅ exact |
| Aucune couche HTTP / route / ORM / entité | arbre complet `git ls-tree -r origin/main` | ✅ exact |

Conclusion : `HEAD` = `origin/main` = `6d4f877`. Les **2 domaines** restent les mêmes (`facturation-tgc` cœur, `journalisation-applicative` support), mais leur **contenu a été mis à jour** pour refléter le taux par ligne, les deux garde-fous d'exception, et la migration Monolog 3.x. L'ancienne `HYPOTHÈSE` Monolog est close par le code. Carte réconciliée et exacte au SHA courant.
