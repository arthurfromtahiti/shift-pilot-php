# Carte des domaines — shift-pilot-php

> **Confiance globale : high** sur chacun des 2 domaines identifiés, mais **couverture fonctionnelle très étroite** — voir *Incertitudes*.
> Dépôt analysé au SHA `5f5c8ee00765beb04be08b5bcb089066c36a0f30` (`HEAD` = `origin/main`, branche par défaut `main`).
> Produite par le run **CLA-171** (Découverte de domaines, 2026-08-04), en **lecture seule du code**. Aucun accès base fourni à ce stade.
> Un `.onboarding/` préexistait dans le checkout (jamais poussé sur le distant, run CLA-169). Chaque affirmation de cette carte a été **re-vérifiée fichier par fichier sur le code courant** par le présent run — voir *Vérification* en fin de document. Le code au SHA `5f5c8ee` est identique à l'état d'écriture initial : aucune dérive.

## Nature du projet

Bibliothèque PHP (`type: project`, autoload PSR-4 `App\` → `src/`) **sans couche HTTP** : ni contrôleur, ni route, ni framework web, ni ORM (`VÉRIFIÉ_CODE` — l'arbre `origin/main` ne contient que `src/AppLogger.php`, `src/InvoiceCalculator.php`, `tests/InvoiceCalculatorTest.php`, plus `README.md`, `composer.json`, `phpunit.xml`, `.gitignore`). Le cœur métier est le **calcul de factures avec la TGC** (taxe générale sur la consommation, Polynésie française), montants en **francs CFP** entiers. Une classe de **journalisation** (Monolog) encadre l'émission des factures et les erreurs de calcul. Dépôt volontairement minimal, présenté comme « pilote » (`README.md` ; `composer.json` : *« Pilote de test SHIFT/Paperclip — facturation TGC »*).

## Domaines

### Facturation TGC (`facturation-tgc`)
- **Catégorie** : métier
- **Priorité** : cœur — sans ce calcul, le logiciel n'a pas de raison d'être.
- **Confiance** : high
- **Description** : calcul du total **hors taxe** puis **toutes taxes comprises** d'une facture composée de lignes. La TGC s'applique à deux taux : **standard 16 %** (`TGC_STANDARD = 0.16`) et **réduit 5 %** (`TGC_REDUIT = 0.05`, ex. « produit première nécessité »). Montants en **francs CFP** entiers ; TTC arrondi via `(int) round($ht * (1 + $taux))`.
- **Entités** : aucune entité ORM. La **ligne de facture** est un tableau associatif `{label: string, quantite: int, prixUnitaire: int}` (docblock de `InvoiceCalculator::totalHorsTaxe`, `VÉRIFIÉ_CODE` `src/InvoiceCalculator.php:15`). Constantes de domaine `TGC_STANDARD`, `TGC_REDUIT` (`src/InvoiceCalculator.php:11-12`).
- **Routes / points d'entrée** : **aucune route** — appelé comme bibliothèque via les méthodes publiques `totalHorsTaxe(array $lignes): int` et `totalTtc(array $lignes, bool $tauxReduit = false): int` (`src/InvoiceCalculator.php:17,26`). Aucun contrôleur ni route localisé malgré parcours complet de l'arbre.
- **Indices de rattachement** : classe `InvoiceCalculator` ; méthodes `totalHorsTaxe`, `totalTtc` ; constantes `TGC_*` ; termes `facture`, `TGC`, `taux réduit` ; chemin `src/InvoiceCalculator.php`.
- **Types de workflows attendus** : calcul HT → TTC d'une facture ; choix du taux (standard vs réduit) ; agrégation des lignes ; arrondi monétaire.
- **Preuves** : `src/InvoiceCalculator.php` (lu intégralement) ; `tests/InvoiceCalculatorTest.php` (`testTotalHorsTaxe` = 25000, `testTotalTtcTauxStandard` = 11600, `testTotalTtcTauxReduit` = 10500).
- **Dépend de la base** : non — aucun des trois signaux du §6 (pas d'accès schéma ; aucune entité étendue par un champ layout/blocks/config ; aucun renderer ni décodage récursif de structure).

### Journalisation applicative (`journalisation-applicative`)
- **Catégorie** : technique (transverse)
- **Priorité** : support
- **Confiance** : high (code lu intégralement) — périmètre étroit.
- **Description** : émission de journaux applicatifs via **Monolog**. Deux événements sont exposés en dur : `factureEmise(int $totalTtc)` (niveau *info*, contexte `total_ttc`) et `erreurCalcul(string $message)` (niveau *error*, contexte `detail`). Canal par défaut `facturation`, sortie par défaut `php://stderr`, tous deux paramétrables au constructeur.
- **Entités** : aucune. Classe `AppLogger` encapsulant `Monolog\Logger` + `StreamHandler` (`src/AppLogger.php:13-19`).
- **Routes / points d'entrée** : aucune route — bibliothèque. Points d'entrée : `__construct(string $canal = 'facturation', string $fichier = 'php://stderr')`, `factureEmise(int $totalTtc): void`, `erreurCalcul(string $message): void` (`src/AppLogger.php:15,21,26`).
- **Indices de rattachement** : classe `AppLogger` ; `Monolog\Logger`, `StreamHandler`, `pushHandler` ; méthodes `addInfo`, `addError` ; chemin `src/AppLogger.php`.
- **Types de workflows attendus** : journalisation d'une facture émise ; journalisation d'une erreur de calcul ; configuration du canal/handler de sortie.
- **Preuves** : `src/AppLogger.php` (lu intégralement) ; dépendance `monolog/monolog: ^1.25` (`composer.json:8`).
- **Dépend de la base** : non.
- **Observation technique à transmettre en aval** (ne fait pas un domaine) : le docblock `src/AppLogger.php:9` qualifie lui-même `addInfo()`/`addError()` d'**API Monolog 1.x** (`VÉRIFIÉ_CODE`), et `composer.json:8` borne la version à `^1.25` — donc cohérent en l'état. `HYPOTHÈSE` (connaissance externe au dépôt, non vérifiable ici) : cette API est retirée à partir de Monolog 2.0 au profit de `info()`/`error()`. À faire trancher/étayer par l'étape d'audit avant toute montée de version — signalé comme point de fragilité potentiel, pas comme jugement.

## Incertitudes

- **Seulement 2 domaines, sous le plancher usuel de 4** (méthode §5). Ce n'est **pas** une fusion abusive : le dépôt ne contient que 3 fichiers source (`VÉRIFIÉ_CODE` sur l'arbre `origin/main`). Découper « calcul HT » et « calcul TTC » en domaines distincts confondrait domaine et module — refusé. La rareté vient de la matière, pas de l'analyse (socle : *matière pauvre → livrable court et honnête*).
- **Aucune couche HTTP / persistance / entité ORM.** À confirmer par le board : ce périmètre bibliothèque est-il la cible du projet, ou un socle destiné à grossir (contrôleurs, base, front) ? La réponse conditionne ce que l'Analyste de workflows pourra attendre comme points d'entrée.
- **Détection « contenu piloté par la base » partielle** : sans accès base ni schéma fourni, seuls 2 des 3 signaux du §6 ont pu être évalués (entité étendue, code exécutable) — aucun trouvé. Le signal *schéma* reste non évaluable ; en l'état, aucun domaine ne dépend de la base.
- **Modèle monétaire** : prix et totaux en **entiers** (pas de sous-unité), devise francs CFP — confirmé par les assertions de test. À valider comme règle métier stable (pas de centimes attendus).

## Vérification (run CLA-171, 2026-08-04, SHA `5f5c8ee`)

Un `.onboarding/` préexistait dans le checkout (jamais poussé sur le distant, run CLA-169). Le présent run a **relu tout le code au SHA courant** et confronté chaque affirmation — non de mémoire, ligne par ligne. Contrôles (`VÉRIFIÉ_CODE`) :

| Claim de la carte | Source vérifiée | Verdict |
|---|---|---|
| 2 classes seules (`InvoiceCalculator`, `AppLogger`) | `git ls-tree -r origin/main` = 7 fichiers ; `src/` = ces 2 classes | ✅ exact |
| `TGC_STANDARD = 0.16`, `TGC_REDUIT = 0.05` | `src/InvoiceCalculator.php:11-12` | ✅ exact |
| Ligne = `{label, quantite, prixUnitaire}`, francs CFP entiers | docblock `src/InvoiceCalculator.php:15` | ✅ exact |
| `totalHorsTaxe()` / `totalTtc()`, TTC = `(int) round($ht*(1+$taux))` | `src/InvoiceCalculator.php:17-31` | ✅ exact |
| Monolog 1.x `addInfo`/`addError`, canal `facturation`, `php://stderr` | `src/AppLogger.php:15-29` | ✅ exact |
| `factureEmise(int)` info `total_ttc` / `erreurCalcul(string)` error `detail` | `src/AppLogger.php:21-29` | ✅ exact |
| `monolog/monolog: ^1.25` | `composer.json:8` | ✅ exact |
| Tests 25000 / 11600 / 10500 | `tests/InvoiceCalculatorTest.php:17,24,31` | ✅ exact |
| Aucune couche HTTP / route / ORM / entité | arbre complet `git ls-tree -r origin/main` | ✅ exact |
| API Monolog 1.x (`addInfo`/`addError`) déclarée dans le docblock | `src/AppLogger.php:9` | ✅ exact — le « retrait en 2.0 » reste `HYPOTHÈSE` (hors dépôt), à trancher en audit |

Conclusion : `HEAD` = `origin/main` = `5f5c8ee` ; le code est **identique** à l'état sur lequel la carte a été écrite. Aucune dérive, rien à réécrire sur le fond ; la seule modification de fond apportée par ce run est la **requalification en `HYPOTHÈSE`** du retrait de l'API Monolog en 2.0 (connaissance externe non prouvable depuis le dépôt), pour rester strictement dans la preuve.

### Re-confrontation run CLA-176 (2026-08-04, SHA `5f5c8ee`)

Réconciliation §2ter du présent run. `HEAD` toujours à `5f5c8ee` (= `origin/main`, unique commit `Seed pilot PHP`), arbre inchangé (`git ls-tree -r` = 7 fichiers versionnés, `src/` = `InvoiceCalculator.php` + `AppLogger.php`). Chaque affirmation de la table ci-dessus a été **re-vérifiée sur le code courant** (`VÉRIFIÉ_CODE`) : `TGC_STANDARD = 0.16` / `TGC_REDUIT = 0.05` (`src/InvoiceCalculator.php:11-12`), `addInfo`/`addError` Monolog 1.x (`src/AppLogger.php:9,23,28`), `monolog/monolog: ^1.25` (`composer.json:8`), assertions de test 25000/11600/10500 (`tests/InvoiceCalculatorTest.php:17,24,31`). **Aucune dérive** ; les 2 domaines et la requalification `HYPOTHÈSE` du retrait Monolog 2.0 restent valables tels quels. Carte confirmée exacte et à jour.
