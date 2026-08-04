# Contexte projet — shift-pilot-php

## Nature et ambition

**shift-pilot-php** est une **bibliothèque PHP minimale** de calcul de facturation avec la **TGC** (taxe générale sur la consommation, régime fiscal Polynésie française). Elle implémente deux fonctions mathématiques : calcul du total hors taxe et calcul du total toutes taxes comprises, en appliquant deux taux fixes (standard 16 % et réduit 5 %).

Le dépôt porte explicitement la mention « **pilote de test SHIFT/Paperclip** » (`README.md`) : c'est un cas d'usage minimal servant de point de départ à la chaîne d'onboarding Paperclip. Pas de contrôleur web, pas de base de données, pas de génération de document — juste le calcul, accompagné d'une journalisation applicative via Monolog 1.x.

**Contexte d'usage** : la bibliothèque est destinée à être consommée par une application hôte (non présente dans ce dépôt) qui l'instanciera, lui fournira des lignes de facture et exploitera les résultats numériques. Le projet **ne déploie rien seul** : c'est un composant.

## Structure du dépôt

```
shift-pilot-php/
├── src/
│   ├── InvoiceCalculator.php    # Unique classe métier
│   └── AppLogger.php             # Adaptateur technique Monolog
├── tests/
│   └── InvoiceCalculatorTest.php # 3 tests PHPUnit nominaux
├── composer.json                 # Dépendances (Monolog 1.x, PHPUnit)
├── phpunit.xml                   # Configuration des tests
└── README.md                      # Présentation du pilote
```

**Clés organisationnelles** :
- Namespace PSR-4 : `App\` → `src/`
- Tests : namespace `App\Tests\`, route `tests/`
- PHP minimum 8.0 (`composer.json:6`)
- Dépendance unique métier : `monolog/monolog: ^1.25` (Monolog 1.x, API stable)

## Domaines métier

Le projet couvre **deux domaines** :

| Domaine | Rôle | Exposition |
|---|---|---|
| **Facturation TGC** | Cœur métier — calcul du montant HT/TTC d'une facture | Méthodes publiques `totalHorsTaxe()`, `totalTtc()` |
| **Journalisation applicative** | Support technique — enregistrement des événements et erreurs via Monolog | Méthodes publiques `factureEmise()`, `erreurCalcul()` |

Aucun autre domaine n'est présent dans le code.

## Périmètre couvert, périmètre absent

### Ce que la bibliothèque fait

- Agrège une liste de lignes (label, quantité, prix unitaire en francs CFP entiers)
- Calcule le total hors taxe par sommation (`quantité × prixUnitaire`)
- Applique un taux TGC unique (standard 16 % ou réduit 5 %) à l'ensemble du total
- Arrondit au franc CFP entier (`(int) round()`)
- Enregistre l'émission d'une facture ou l'occurrence d'une erreur sur un flux de sortie Monolog

### Ce que la bibliothèque ne fait pas

- **Pas d'HTTP** : aucun contrôleur, aucune route, aucun framework web
- **Pas de persistance** : aucune base de données, aucune entité ORM, aucune table
- **Pas de document** : pas de génération PDF, pas de facture structurée, pas de sérialisation
- **Pas d'intégration interne** : aucun code dans le dépôt ne combine `InvoiceCalculator` et `AppLogger` — l'orchestration est déléguée à l'application hôte
- **Pas de gestion commerciale** : pas de client, pas de fournisseur, pas de remise par ligne, pas d'avoir, pas de TVA progressive, pas de choix de taux à grain fin (une facture = un seul taux)

Cette liste définit le **contrat** de la bibliothèque. Tout ce qui n'est pas ici ne doit pas être attendu du dépôt.

## Limitations architecturales

Deux limites sont **inscrites dans l'API** et ne peuvent être contournées qu'avec une refonte :

1. **Taux TGC unique par facture** (`src/InvoiceCalculator.php:26`)
   - Signature : `totalTtc(array $lignes, bool $tauxReduit = false): int`
   - Effet : le taux sélectionné (`$tauxReduit`) s'applique à l'intégralité du total HT
   - Conséquence : impossible de calculer une facture ayant des lignes à 16 % et des lignes à 5 % en un seul appel
   - Contournement possible : appeler `totalTtc` deux fois (une fois par sous-ensemble de lignes avec le taux correspondant) et sommer les résultats — non documenté, non testé

2. **Pas d'interface de logger** (`src/AppLogger.php`)
   - Le constructeur instancie directement `Monolog\Logger` et `StreamHandler` sans injection
   - Conséquence : impossible de substituer un handler alternatif (base de données, API, mémoire pour tests) sans modifier la classe
   - Contournement possible : sous-classer `AppLogger` — pas documenté, pas testé

Ces limitations ne sont **pas des défauts** pour un pilote : elles sont appropriées à la minimalité du projet. Elles deviennent des points d'attention si le projet évolue.

## Attentes de confiance

### Ce qu'on sait avec certitude (`VÉRIFIÉ_CODE`)

- Implémentation lue intégralement : deux fichiers source seuls, pas de code caché
- Constantes TGC : `TGC_STANDARD = 0.16` et `TGC_REDUIT = 0.05` (confirmées ligne par ligne)
- Trois tests valident les calculs nominaux (HT, TTC standard, TTC réduit)
- Aucune couche HTTP/ORM/persistance : vérifié sur l'arbre complet du dépôt (`git ls-tree`)
- Dépendances explicites : seules Monolog 1.x (logger) et PHPUnit (tests)

**Confiance niveau** : **high** sur le code source et son implémentation.

### Ce qu'on sait par hypothèse (`HYPOTHÈSE`)

- Taux TGC (16 % et 5 %) : contexte externe, supposé correspondre aux taux polynésiens. Aucune source légale dans le dépôt — à valider par le board métier
- Mode d'arrondi : `round()` appelé sans argument explicite — utilise `PHP_ROUND_HALF_UP` par défaut (hypothèse d'implémentation PHP). Conformité avec la réglementation CFP non sourcée dans le dépôt
- Migration Monolog 1.x vers 2.x : les méthodes `addInfo()`/`addError()` risquent d'être retirées en Monolog 2.0 (connaissance externe) — la contrainte `^1.25` protège aujourd'hui, mais ce risque reste latent

**Confiance niveau** : **medium** sur les intentions métier et la conformité réglementaire ; **low** sur la stabilité de la dépendance Monolog si la contrainte est relâchée.

### Ce qu'on ignore (`INCONNU`)

- Qui sont les consommateurs finaux ? (application interne, API publique, autre bibliothèque)
- Le projet grossira-t-il (HTTP, ORM, documents) ou restera-t-il un composant pur ?
- Le format des factures vides est-il volontaire (0 F CFP) ou une erreur à rejeter ?
- Les taux TGC sont-ils figés ou peuvent-ils évoluer sans notification du dépôt ?

Ces inconnues sont **des questions pour le board**, pas des défauts du livrable.

## Points de fragilité repérés

| Point | Gravité | Détail | Étape où l'adresser |
|---|---|---|---|
| **Dépendance à Monolog 1.x** | Moyen | API `addInfo`/`addError` déclarée Monolog 1.x ; vigilance requise lors d'une montée de version | Architecture (avant toute montée de version) |
| **Pas de `composer.lock`** | Moyen | Dépendances non reproductibles entre environnements (Monolog peut varier dans `^1.25`) | Architecture (reproductibilité) |
| **`AppLogger` non testé** | Moyen | Aucun test pour cette classe — régression Monolog non interceptée | Tests (couverture) |
| **Cas limites non couverts** | Faible | Tableau vide, clé manquante, valeur négative : comportement non prouvé | Tests (complétude) |
| **Absence d'interface sur `AppLogger`** | Faible | Logger instancié directement — impossible de substituer pour tests | Architecture (injectabilité) |
| **Taux unique par facture** | Faible pour pilote, Moyen pour production | Limitation architecturale, appropriée pour un pilote | Fonctionnel (à clarifier si le scope grossit) |

Aucune de ces fragilités ne rend le pilote inopérant. Elles ont toutes des remèdes documentés.

## Charge de travail et ressources

- **Checkout** : 7 fichiers versionnés (2 classes, 1 test, 3 configs, README)
- **Développement estimé** : ~200 lignes de PHP productif
- **Couverture de test actuelle** : 3 cas nominaux sur 1 classe ; 0 sur l'autre classe
- **Dépôt partagé avec** : aucun (projet monolithique, un seul workspace)
- **Fréquence d'accès base** : jamais (zéro persistance)

## À retenir pour la suite

1. Ce projet est un **pilote volontairement minimal** — ne pas l'étendre avec du code de production absent
2. Les **taux TGC** (16 %, 5 %) sont une décision métier à valider avec le board ; les constantes les rendent localisables mais non vérifiées
3. La dépendance **Monolog 1.x** est protégée par la contrainte `^1.25` ; toute montée de version majeure requiert une vérification de compatibilité de l'API
4. Aucune **intégration interne** entre `InvoiceCalculator` et `AppLogger` ne doit être ajoutée dans ce dépôt : c'est l'affaire de l'application hôte
5. Le **périmètre absent** (HTTP, ORM, documents) n'est pas une limitation, c'est une délimitation intentionnelle — aucune demand de l'étendre n'a été reçue du board

---

**Branche** : `main` (unique)  
**SHA référence** : `5f5c8ee00765beb04be08b5bcb089066c36a0f30` (dernier commit : *Seed pilot PHP*)  
**Date de dernière vérification** : 2026-08-04
