# Cartographie du code — shift-pilot-php

## Inventory et navigation

### Fichiers productifs

```
src/
├── InvoiceCalculator.php  [57 lignes]   Unique classe métier — calcul HT/TTC
└── AppLogger.php          [30 lignes]   Adaptateur technique Monolog 1.x
```

### Fichiers de configuration et support

```
.
├── composer.json          [19 lignes]   Déclaration des dépendances (Monolog, PHPUnit)
├── composer.lock          [absent]      ⚠️ Builds non reproductibles
├── phpunit.xml            [13 lignes]   Configuration PHPUnit (pas de couverture)
└── README.md              [13 lignes]   Présentation du pilote
```

### Fichiers de test

```
tests/
└── InvoiceCalculatorTest.php  [102 lignes]   12 tests PHPUnit (nominaux + limites)
```

**Total** : 7 fichiers versionnés ; 2 classes PHP ; 12 tests exécutables.

---

## Architecture logique

```
┌─────────────────────────────────────────────────────────────┐
│                    Application hôte                         │
│                  (consommatrice)                            │
└────┬──────────────────────────────────────────────────────┬─┘
     │ instancie + appelle                                  │
     ▼                                                       ▼
┌──────────────────────────┐              ┌──────────────────────────┐
│  App\InvoiceCalculator   │              │    App\AppLogger         │
│  (src/...)               │              │    (src/...)             │
├──────────────────────────┤              ├──────────────────────────┤
│ const TGC_STANDARD=0.16  │              │ __construct(canal, fichier)│
│ const TGC_REDUIT=0.05    │              │ factureEmise(totalTtc)    │
│ totalHorsTaxe(lignes)    │              │ erreurCalcul(message)    │
│ totalTtc(lignes)         │              │ wraps: Monolog\Logger    │
└──────────────────────────┘              └──────────────────────────┘
     │                                            │
     │ (aucune dépendance)                        │ depends on:
     └──────────────────────┬─────────────────────┤
                            │                     │
                       No internal                │
                       orchestration       Monolog 1.x
                                             (^1.25)
```

**Pas de couche d'intégration** : les deux classes coexistent sans code qui les lie. L'application hôte les orchestre.

---

## Classe 1 : `App\InvoiceCalculator`

### Fichier
`src/InvoiceCalculator.php` — 57 lignes

### Responsabilité
Calcul du montant HT/TTC d'une facture selon les règles TGC.

### Namespace et autoload
- Namespace : `App\`
- Autoload PSR-4 : `"App\\"` → `src/` (déclaré en `composer.json:15`)

### Constantes de classe

| Nom | Valeur | Rôle |
|---|---|---|
| `TGC_STANDARD` | `0.16` | Taux standard (16 %) — ligne 11 |
| `TGC_REDUIT` | `0.05` | Taux réduit (5 %) — ligne 12 |

**Point de vigilance** : ces constantes sont l'unique source d'vérité pour les taux TGC. Toute modification doit passer par ici.

### Méthodes publiques

#### `totalHorsTaxe(array $lignes): int`

**Signature** : ligne 19  
**Paramètre** :
- `$lignes` : array de lignes, chaque ligne = `{label: string, quantite: int, prixUnitaire: int}`

**Retour** : somme des `quantite × prixUnitaire` pour chaque ligne, en francs CFP entiers

**Exceptions levées** :
- `\InvalidArgumentException` : si une ligne manque les clés `quantite` ou `prixUnitaire` (ligne 23)
- `\OverflowException` : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN` (lignes 29-31)

**Implémentation** : lignes 21-33
```php
$total = 0;
foreach ($lignes as $ligne) {
    if (!isset($ligne['quantite'], $ligne['prixUnitaire'])) {
        throw new \InvalidArgumentException('Chaque ligne doit contenir "quantite" et "prixUnitaire".');
    }
    $total += $ligne['quantite'] * $ligne['prixUnitaire'];
}
$rounded = round($total);
if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) {
    throw new \OverflowException('Le total hors taxe dépasse les bornes de PHP_INT_MAX.');
}
return (int) $rounded;
```

**Points critiques** :
- Valide la présence des clés `quantite` et `prixUnitaire` avant de les accéder (ligne 23)
- Vérifie le débordement après arrondi avant de retourner (lignes 29-31)
- Pas de typehint sur le contenu des lignes (pas de vérification à la compilation)

**Test couvrant** : `testTotalHorsTaxe` — 2 lignes, résultat 25000 ✓

#### `totalTtc(array $lignes): int`

**Signature** : ligne 41  
**Paramètre** :
- `$lignes` : array de lignes, chaque ligne = `{label: string, quantite: int, prixUnitaire: int, taux?: float}` ; chaque ligne peut porter son propre taux TGC via la clé optionnelle `taux`

**Retour** : somme des lignes avec leur TGC respective appliquée, arrondie au franc CFP entier

**Exceptions levées** :
- `\InvalidArgumentException` : si une ligne manque les clés `quantite` ou `prixUnitaire` (ligne 45)
- `\OverflowException` : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN` (lignes 52-54)

**Implémentation** : lignes 43-56
```php
$total = 0.0;
foreach ($lignes as $ligne) {
    if (!isset($ligne['quantite'], $ligne['prixUnitaire'])) {
        throw new \InvalidArgumentException('Chaque ligne doit contenir "quantite" et "prixUnitaire".');
    }
    $taux = $ligne['taux'] ?? self::TGC_STANDARD;
    $total += $ligne['quantite'] * $ligne['prixUnitaire'] * (1 + $taux);
}
$rounded = round($total);
if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) {
    throw new \OverflowException('Le total TTC dépasse les bornes de PHP_INT_MAX.');
}
return (int) $rounded;
```

**Points critiques** :
- Valide la présence des clés `quantite` et `prixUnitaire` avant de les accéder (ligne 45)
- Accumulateur en `float` pour laisser la précision intermédiaire (ligne 43)
- Support de taux par ligne via la clé optionnelle `taux` (ligne 48) — défaut : `TGC_STANDARD`
- Arrondit une seule fois, après la somme totale (ligne 51)
- Vérifie le débordement avant de retourner (lignes 52-55)

**Tests couvrant** :
- `testTotalTtcTauxStandard` (ligne 20) : HT=10000, taux=16 %, résultat 11600 ✓
- `testTotalTtcTauxReduit` (ligne 27) : HT=10000, taux=5 %, résultat 10500 ✓
- `testTotalTtcTauxMixte` (ligne 34) : taux mixtes, résultat 22100 ✓
- `testTotalTtcSansTauxUtiliseTauxStandard` (ligne 47) : taux par défaut utilisé ✓

### Dépendances
Aucune — classe autonome, zéro dépendance Composer.

### Risques et dette
| Risque | Localisation | Sévérité | Mitigation |
|---|---|---|---|
| Tableau vide retourne 0 | lignes 21-32 | Faible | Documenter le comportement ou le rejeter |
| Valeur négative non rejetée | ligne 26 | Faible | Documenter ou valider |
| Débordement d'entier | lignes 29-31, 52-54 | Élevé | ✅ Levée d'exception `OverflowException` (résolu en fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-426) |

---

## Classe 2 : `App\AppLogger`

### Fichier
`src/AppLogger.php` — 30 lignes

### Responsabilité
Adaptateur technique pour l'enregistrement des événements de facturation via Monolog 1.x.

### Namespace et autoload
- Namespace : `App\`
- Autoload PSR-4 : idem `InvoiceCalculator`

### Propriété de classe

| Nom | Type | Initialisé | Rôle |
|---|---|---|---|
| `$logger` | `Monolog\Logger` | Constructeur (ligne 17) | Instance Monolog interne |

### Méthodes

#### Constructeur `__construct(string $canal = 'facturation', string $fichier = 'php://stderr')`

**Signature** : ligne 15  
**Paramètres** :
- `$canal` : nom du canal Monolog (défaut : `facturation`)
- `$fichier` : chemin ou flux de destination (défaut : sortie d'erreur standard)

**Implémentation** : lignes 17-18
```php
$this->logger = new Logger($canal);
$this->logger->pushHandler(new StreamHandler($fichier));
```

**Points critiques** :
- Instanciation directe de `Logger` et `StreamHandler` — pas d'injection
- Pas d'interface — impossible de substituer pour tests sans sous-classement
- Dépend directement de `Monolog\Logger` et `Monolog\Handler\StreamHandler`

**Dépendance Monolog** : `monolog/monolog: ^1.25` (déclaré en `composer.json:8`)

#### `factureEmise(int $totalTtc): void`

**Signature** : ligne 21  
**Paramètre** : `$totalTtc` — montant TTC en francs CFP entiers

**Implémentation** : lignes 23-24
```php
$this->logger->addInfo('Facture émise', ['total_ttc' => $totalTtc]);
```

**Points critiques** :
- Utilise l'API Monolog 1.x `addInfo()` (déclaration de compatibilité : docblock ligne 9)
- Ajoute un contexte Monolog `total_ttc` avec la valeur
- Pas de gestion d'exception — les erreurs Monolog remontent à l'appelant

**État** : `HYPOTHÈSE` — l'API `addInfo()` serait retirée en Monolog 2.0 (connaissance externe, non sourcée dans le dépôt)

#### `erreurCalcul(string $message): void`

**Signature** : ligne 26  
**Paramètre** : `$message` — description de l'erreur

**Implémentation** : lignes 28-29
```php
$this->logger->addError('Erreur de calcul', ['detail' => $message]);
```

**Points critiques** :
- Utilise l'API Monolog 1.x `addError()` (même déclaration de compatibilité)
- Ajoute un contexte Monolog `detail` avec le message
- Pas de gestion d'exception

### Docblock et déclarations

Ligne 9-11 : docblock indiquant l'utilisation de l'API Monolog 1.x
```php
/**
 * @uses Monolog\Logger (v1.x API: addInfo/addError) ...
```

Cette déclaration **est importante** : elle documente que le code dépend spécifiquement des méthodes `addInfo`/`addError` qui sont Monolog 1.x, pas 2.x+.

### Dépendances
| Dépendance | Version | Déclaration |
|---|---|---|
| `Monolog` | `^1.25` | `composer.json:8` |

### Risques et dette
| Risque | Localisation | Sévérité | Mitigation |
|---|---|---|---|
| Dépendance à l'API Monolog 1.x | lignes 23,28 + docblock 9 | Moyen | Vigilance lors d'une montée vers Monolog 2.x ; `addInfo()`/`addError()` seraient à adapter |
| Pas d'interface pour injection | lignes 17-18 | Moyen | Extraire une interface `LoggerInterface` |
| Aucun test | (fichier non couvert) | Moyen | Ajouter `AppLoggerTest.php` |
| Pas de gestion d'exception | lignes 23,28 | Faible | Les erreurs Monolog remontent à l'appelant — à documenter |
| Pas de `composer.lock` | (racine) | Moyen | Créer et versionner `composer.lock` |

---

## Dépendances Composer

### Production

| Package | Version déclarée | But | Fichier |
|---|---|---|---|
| `monolog/monolog` | `^1.25` | Journalisation applicative | `src/AppLogger.php` |

**Vigilance** : l'API utilisée (`addInfo`, `addError`) appartient à Monolog 1.x. La contrainte `^1.25` protège le code contre des versions majeures qui changeraient cette API. Point de vigilance lors d'une montée de version.

### Tests/Développement

| Package | Version déclarée | But | Fichier |
|---|---|---|---|
| `phpunit/phpunit` | `^9.6` | Tests unitaires | `tests/InvoiceCalculatorTest.php` |

**État** : PHPUnit 9.x sans configuration de couverture (`phpunit.xml` ne déclare pas `<coverage>`).

### PHP version minimale

Déclaré en `composer.json:6` : `"php": ">=8.0"`

**Observation** : le code n'exploite aucune syntaxe PHP 8.1+ (pas de `match`, pas de constructor promotion, pas de named arguments) — le minimum 8.0 est volontairement large.

---

## Configuration Composer

**Fichier** : `composer.json` — 19 lignes

**Éléments clés** :

| Clé | Valeur | Rôle |
|---|---|---|
| `name` | `shift/shift-pilot-php` | Identifiant package |
| `type` | `library` | Type : bibliothèque (pas une application) |
| `require` | `php: >=8.0`, `monolog/monolog: ^1.25` | Dépendances production |
| `require-dev` | `phpunit/phpunit: ^11.0` | Dépendances tests |
| `autoload.psr-4` | `App\\` → `src/` | Autoload PSR-4 |
| `autoload.tests.psr-4` | `App\\Tests\\` → `tests/` | Autoload tests |
| `scripts.test` | `phpunit` | Commande `composer test` |

**Absence notable** : pas de `composer.lock` (README:13 le mentionne explicitement). Conséquence : deux installations peuvent résoudre des versions différentes de Monolog dans la plage `^1.25`.

---

## Configuration PHPUnit

**Fichier** : `phpunit.xml` — 13 lignes

**Éléments clés** :

```xml
<testsuite name="invoice-calculator">
  <directory>tests</directory>
</testsuite>
<bootstrap filename="vendor/autoload.php"/>
```

**Absence notable** : pas de `<coverage>` — la couverture de code n'est ni collectée ni rapportée.

---

## Chemins critiques dans le code

| Chemin | Critique pour | Localisation | Points d'attention |
|---|---|---|---|
| Calcul HT | Tous les calculs | `src/InvoiceCalculator.php:21-26` | Pas de validation de clés |
| Sélection de taux | TTC au taux correct | `src/InvoiceCalculator.php:42` | Booléen unique, pas de taux mixte |
| Arrondi final | Exactitude du TTC | `src/InvoiceCalculator.php:27,45` | `(int) round()` explicite |
| Construction du logger | Journalisation | `src/AppLogger.php:17-18` | Instanciation directe Monolog |
| API `addInfo`/`addError` | Événements enregistrés | `src/AppLogger.php:23,28` | Monolog 1.x seulement |

---

## Flux d'appel nominal

```
Application hôte
   │
   ├─→ new InvoiceCalculator()
   │      │
   │      └─→ initialise constantes TGC_STANDARD, TGC_REDUIT
   │
   ├─→ $calc->totalHorsTaxe($lignes)
   │      │
   │      └─→ Σ(quantité × prixUnitaire)
   │           └─→ retourne int
   │
   ├─→ $calc->totalTtc($lignes)
   │      │
   │      ├─→ pour chaque ligne, lit taux optionnel (défaut : TGC_STANDARD)
   │      │
   │      ├─→ accumule ligne × (1 + taux)
   │      │
   │      └─→ (int) round(total) → retourne int
   │
   └─→ new AppLogger('facturation', 'php://stderr')
          │
          ├─→ new Logger('facturation')
          │
          └─→ pushHandler(new StreamHandler('php://stderr'))
```

---

## Absence notables

| Absence | Signification | Implication |
|---|---|---|
| Pas de `composer.lock` | Builds non reproductibles | Deux `composer install` peuvent différer sur Monolog |
| Pas de contrôleur | Pas d'HTTP | La bibliothèque n'est jamais appelée directement par un navigateur |
| Pas d'ORM | Pas de persistance | Les factures existent en mémoire, ne sont jamais sauvegardées |
| Pas d'intégration `InvoiceCalculator` + `AppLogger` | Orchestration en amont | L'application hôte doit lier les deux classes |
| Pas de test `AppLogger` | `AppLogger` non validé | Régression Monolog ne serait détectée que en production |
| Pas de test cas limites | Comportement non prouvé | Tableau vide, clé manquante, valeur négative : non testés |
| Pas de `<coverage>` en PHPUnit | Couverture inconnue | La proportion de lignes testées n'est pas mesurée |

---

## Langage d'implémentation

**PHP 8.0+**

**Paradigme** : procédural avec classes (pas de patterns avancés : pas d'interface, pas d'héritage, pas de traits)

**Style de code** :
- Pas de type hints sur les paramètres (PHP 7 style, compatible 8.0+)
- Pas d'union types
- Pas de named arguments
- Pas de constructor promotion
- Docblocks présents (pour l'IDE et la documentation)

---

## Points de vigilance pour les évolutions futures

1. **Montée de version Monolog** — si la contrainte `^1.25` est relâchée, vérifier que l'API reste compatible (`addInfo()`, `addError()`)
2. **Taux mixte** — si le besoin émerge, refonte d'API requise (paramètre array de taux par ligne, ou appels multiples documentés)
3. **Validation des lignes** — actuellement zéro validation ; si le besoin de robustesse augmente, ajouter des gardes
4. **Composer.lock** — créer et versionner pour reproductibilité
5. **Couverture de test** — activer `<coverage>` dans `phpunit.xml` et tester `AppLogger`

---

**Branche** : `main` (issue: SHIAAAAAAAAAAAAAAAAAAAAAAAA-426)  
**SHA référence** : `e5a7644` (fix: lever OverflowException si totalHorsTaxe/totalTtc dépasse PHP_INT_MAX)  
**Date de dernière vérification** : 2026-08-06
