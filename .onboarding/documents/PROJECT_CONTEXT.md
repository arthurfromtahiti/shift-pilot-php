# Contexte projet — shift-pilot-php

## Nature et ambition

**shift-pilot-php** est une **bibliothèque PHP minimale** de calcul de facturation avec la **TGC** (taxe générale sur la consommation, régime fiscal Polynésie française). Elle implémente deux fonctions mathématiques : calcul du total hors taxe et calcul du total toutes taxes comprises, en appliquant deux taux fixes (standard 16 % et réduit 5 %).

Le dépôt porte explicitement la mention « **pilote de test SHIFT/Paperclip** » (`README.md`) : c'est un cas d'usage minimal servant de point de départ à la chaîne d'onboarding Paperclip. Pas de contrôleur web, pas de base de données, pas de génération de document — juste le calcul, accompagné d'une journalisation applicative via **Monolog 3.x** (migré en 2026-08-08).

**Contexte d'usage** : la bibliothèque est destinée à être consommée par une application hôte (non présente dans ce dépôt) qui l'instanciera, lui fournira des lignes de facture et exploitera les résultats numériques. Le projet **ne déploie rien seul** : c'est un composant.

## Structure du dépôt

```
shift-pilot-php/
├── src/
│   ├── InvoiceCalculator.php    # Unique classe métier
│   └── AppLogger.php             # Adaptateur technique Monolog
├── tests/
│   └── InvoiceCalculatorTest.php # 12 tests PHPUnit (nominaux, taux mixte, exceptions)
├── composer.json                 # Dépendances (Monolog 3.x, PHPUnit 10.5)
├── composer.lock                 # Verrouille Monolog 3.10.0 (depuis 2026-08-08)
├── phpunit.xml                   # Configuration des tests
└── README.md                      # Présentation du pilote (incohérence PHP 8.0/8.1 détectée)
```

**Clés organisationnelles** :
- Namespace PSR-4 : `App\` → `src/`
- Tests : namespace `App\Tests\`, route `tests/`
- **PHP minimum 8.1** (`composer.json:7` — **NOTE** : README.md annonce 8.0, incohérence détectée)
- **composer.lock** : présent, versionné (Monolog 3.10.0, PHPUnit 10.5.64)
- Dépendance unique métier : `monolog/monolog: ^3.0` (Monolog 3.x, migré 2026-08-08)

## Domaines métier

Le projet couvre **deux domaines** :

| Domaine | Rôle | Exposition |
|---|---|---|
| **Facturation TGC** | Cœur métier — calcul du montant HT/TTC d'une facture | Méthodes publiques `totalHorsTaxe()`, `totalTtc()` |
| **Journalisation applicative** | Support technique — enregistrement des événements et erreurs via Monolog | Méthodes publiques `factureEmise()`, `erreurCalcul()` |

Aucun autre domaine n'est présent dans le code.

## Périmètre couvert, périmètre absent

### Ce que la bibliothèque fait

- Agrège une liste de lignes (label, quantité entière, prix unitaire en francs CFP, taux TGC optionnel)
- Calcule le total hors taxe par sommation (`quantité × prixUnitaire`)
- Applique un taux TGC par ligne (standard 16 % par défaut, ou taux explicite) — supporte les taux mixtes (lignes à 16 % et 5 % sur la même facture)
- Arrondit au franc CFP entier (`(int) round()`)
- Enregistre l'émission d'une facture ou l'occurrence d'une erreur sur un flux de sortie Monolog 3.x

### Ce que la bibliothèque ne fait pas

- **Pas d'HTTP** : aucun contrôleur, aucune route, aucun framework web
- **Pas de persistance** : aucune base de données, aucune entité ORM, aucune table
- **Pas de document** : pas de génération PDF, pas de facture structurée, pas de sérialisation
- **Pas d'intégration interne** : aucun code dans le dépôt ne combine `InvoiceCalculator` et `AppLogger` — l'orchestration est déléguée à l'application hôte
- **Pas de gestion commerciale** : pas de client, pas de fournisseur, pas de remise par ligne, pas d'avoir, pas de TVA progressive

Cette liste définit le **contrat** de la bibliothèque. Tout ce qui n'est pas ici ne doit pas être attendu du dépôt.

## Limitations architecturales

Une limite reste **inscrite dans l'API** et ne peut être contournée qu'avec une refonte :

1. **Pas d'interface de logger** (`src/AppLogger.php`)
   - Le constructeur instancie directement `Monolog\Logger` et `StreamHandler` sans injection
   - Conséquence : impossible de substituer un handler alternatif (base de données, API, mémoire pour tests) sans modifier la classe
   - Contournement possible : sous-classer `AppLogger` — pas documenté, pas testé

Ces limitations ne sont **pas des défauts** pour un pilote : elles sont appropriées à la minimalité du projet. Elles deviennent des points d'attention si le projet évolue.

## Attentes de confiance

### Ce qu'on sait avec certitude (`VÉRIFIÉ_CODE`)

- Implémentation lue intégralement : deux fichiers source seuls, pas de code caché
- Constantes TGC : `TGC_STANDARD = 0.16` et `TGC_REDUIT = 0.05` (confirmées ligne par ligne)
- **12 tests** valident les calculs nominaux, les taux mixtes, le taux par défaut, et les cas d'exception (`\InvalidArgumentException`, `\OverflowException`) — vérifiés en statique ; exécution runtime non observée
- **Gardes ajoutées** : `\InvalidArgumentException` sur clé manquante, `\OverflowException` sur dépassement `PHP_INT_MAX` (depuis 2026-08-08)
- **Taux par ligne** : limitation « taux unique » résolue — factures mixtes testées (test `testTotalTtcTauxMixte`, SHA 7ef6351)
- **Monolog 3.x** : migration effectuée, `composer.json:8` déclare `^3.0`, API mises à jour (`info()` / `error()` appelées aux lignes 23, 28 de `src/AppLogger.php`) ; `composer.lock` présent et versionné, verrouille Monolog 3.10.0
- Aucune couche HTTP/ORM/persistance : vérifié sur l'arbre complet du dépôt
- Dépendances explicites et verrouillées : Monolog 3.10.0, PHPUnit 10.5.64 (depuis 2026-08-08) ; composer.lock présent et versionné

**Confiance niveau** : **high** sur le code source, son implémentation, et la stabilité des dépendances (composer.lock versionné et verrouillant Monolog 3.10.0 et PHPUnit 10.5.64).

### Ce qu'on sait par hypothèse (`HYPOTHÈSE`)

- Taux TGC (16 % et 5 %) : contexte externe, supposé correspondre aux taux polynésiens. Aucune source légale dans le dépôt — à valider par le board métier
- Mode d'arrondi : `round()` appelé sans argument explicite dans `src/InvoiceCalculator.php:28,51` — utiliserait `PHP_ROUND_HALF_UP` par défaut **selon le comportement PHP standard**, non contrôlé explicitement par le code. Conformité avec la réglementation CFP non sourcée dans le dépôt — à valider auprès de l'autorité fiscale polynésienne
- Taux par défaut silencieux : l'absence de clé `taux` replie sur `TGC_STANDARD` (16 %) sans signal — risque de facturation incorrecte chez un consommateur qui omet `taux` pour une ligne à 5 % (documenté dans le FUNCTIONAL_AUDIT)
- **Stabilité future de Monolog** (hypothèse externe) : migrations futures de Monolog (changements d'API majeurs, retrait de Monolog 1.x, incompatibilités) relèvent de la gestion de dépendances Composer et ne peuvent être garanties par ce dépôt. Suivi par la gestion de dépendances et équipe ops selon les nouvelles versions de Monolog

**Confiance niveau** : **medium** sur les intentions métier et la conformité réglementaire ; **high** sur la stabilité actuelle de la dépendance Monolog 3.10.0 (verrouillée dans `composer.lock`).

### Ce qu'on ignore (`INCONNU`)

- Qui sont les consommateurs finaux ? (application interne, API publique, autre bibliothèque)
- Le projet grossira-t-il (HTTP, ORM, documents) ou restera-t-il un composant pur ?
- Le format des factures vides est-il volontaire (0 F CFP) ou une erreur à rejeter ?
- Les taux TGC sont-ils figés ou peuvent-ils évoluer sans notification du dépôt ?

Ces inconnues sont **des questions pour le board**, pas des défauts du livrable.

## Points de fragilité repérés

| Point | Gravité | Détail | État |
|---|---|---|---|
| **Incohérence PHP 8.0 vs 8.1** | Moyen | `README.md:7` dit `>= 8.0` mais `composer.json:7` requiert `>=8.1` | **Détecté en audit** — correction README hors scope du redacteur |
| **Incohérence documentaire composer.lock** | Moyen | `README.md:13` dit « non versionné » mais `composer.lock` est présent et suivi git (Monolog 3.10.0, PHPUnit 10.5.64) | **Détecté en audit** — correction README hors scope du redacteur |
| **`AppLogger` non testé** | Moyen | Aucun test pour cette classe — régression Monolog 3.x non interceptée | À adresser : ajouter tests `AppLoggerTest.php` |
| **Taux par défaut silencieux** | Moyen | Absent de `taux`, replie sur `TGC_STANDARD` (16 %) sans signal — risque facturation 16 % au lieu de 5 % | À documenter : ajouter exemple dans README.md |
| **Taux sans validation de plage** | Faible | Accepte `taux < 0` ou `taux > 1.0` sans erreur | À clarifier : documenter le contrat ou ajouter validation |
| **Pas de validation de facture vide** | Faible | `totalTtc([])` retourne `0 F CFP` sans signal d'erreur | À clarifier : est-ce volontaire ou erreur à rejeter ? |
| **Absence d'interface sur `AppLogger`** | Faible | Logger instancié directement — impossible de substituer pour tests ou autre handler | Architecture : acceptable pour pilote, à refactorer pour production |

Aucune de ces fragilités ne rend le pilote inopérant. Les incohérences documentaires README.md (détectées) et le manque de tests `AppLogger` sont les plus impactantes pour la maintenabilité future.

## Charge de travail et ressources

- **Checkout** : 8 fichiers versionnés (2 classes, 1 test, 4 configs incluant `composer.lock`, README)
- **Développement estimé** : ~100 lignes de PHP métier, ~30 lignes de PHP logging technique
- **Couverture de test actuelle** : 12 tests sur `InvoiceCalculator` (nominaux, taux mixte, exceptions) ; 0 tests sur `AppLogger`
- **Dépôt partagé avec** : aucun (projet monolithique, un seul workspace)
- **Fréquence d'accès base** : jamais (zéro persistance)

## À retenir pour la suite

1. Ce projet est un **pilote volontairement minimal** — ne pas l'étendre avec du code de production absent
2. Les **taux TGC** (16 %, 5 %) sont une décision métier à valider avec le board ; les constantes les rendent localisables mais non vérifiées au-delà du code
3. La dépendance **Monolog 3.x** (migrée de 1.x en 2026-08-08) est verrouillée via `composer.lock` à `3.10.0` ; toute montée de version mineure/majeure requiert vérification de compatibilité de l'API (`info()` / `error()`) ; l'obsolescence d'une version majeure reste une hypothèse externe à valider avec la gestion de dépendances (Composer)
4. **Incohérences documentaires détectées** : `README.md` annonce PHP 8.0 tandis que `composer.json` requiert 8.1 ; `README.md` prétend que `composer.lock` n'est pas versionné alors qu'il est présent. À corriger en priorité.
5. La **capacité taux mixte** (lignes à 16 % et 5 % sur la même facture) est désormais implémentée et testée — limitation d'origine levée
6. Aucune **intégration interne** entre `InvoiceCalculator` et `AppLogger` ne doit être ajoutée dans ce dépôt : c'est l'affaire de l'application hôte
7. Le **périmètre absent** (HTTP, ORM, documents) n'est pas une limitation, c'est une délimitation intentionnelle — aucune demande de l'étendre n'a été reçue du board

---

**Branche** : `main`  
**SHA référence** : `7470fd9` (corrections documentaires critiques)  
**Date de dernière mise à jour** : 2026-08-09  
**Audits de référence** : ARCHITECTURE_AUDIT.md, FUNCTIONAL_AUDIT.md, CODE_HOTSPOTS_AUDIT.md, DATA_MODEL_AUDIT.md, SECURITY_ROBUSTNESS_AUDIT.md, TESTING_AUDIT.md
