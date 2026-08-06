# Cartographie du code — shift-pilot-php

## Inventory et navigation

### Fichiers productifs

```
src/
├── InvoiceCalculator.php  [57 lignes]   Unique classe métier — calcul HT/TTC
└── AppLogger.php          [30 lignes]   Adaptateur technique Monolog 3.x
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
                       orchestration       Monolog 3.x
                                             (^3.0)
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
- `\OverflowException` : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN` (lignes 29-30)

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
- Vérifie le débordement après arrondi avant de retourner (lignes 29-30)
- Pas de typehint sur le contenu des lignes (pas de vérification à la compilation)

**Test couvrant** : `testTotalHorsTaxe` — 2 lignes, résultat 25000 ✓

#### `totalTtc(array $lignes): int`

**Signature** : ligne 41  
**Paramètre** :
- `$lignes` : array de lignes, chaque ligne = `{label: string, quantite: int, prixUnitaire: int, taux?: float}` ; chaque ligne peut porter son propre taux TGC via la clé optionnelle `taux`

**Retour** : somme des lignes avec leur TGC respective appliquée, arrondie au franc CFP entier

**Exceptions levées** :
- `\InvalidArgumentException` : si une ligne manque les clés `quantite` ou `prixUnitaire` (ligne 45)
- `\OverflowException` : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN` (lignes 52-53)

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
- Vérifie le débordement avant de retourner (lignes 52-53)

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
| Débordement d'entier | lignes 29-30, 52-53 | Élevé | ✅ Levée d'exception `OverflowException` (résolu en fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-426) |

---

## Classe 2 : `App\AppLogger`

### Fichier
`src/AppLogger.php` — 30 lignes

### Responsabilité
Adaptateur technique pour l'enregistrement des événements de facturation via Monolog 3.x.

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

**Dépendance Monolog** : `monolog/monolog: ^3.0` (déclaré en `composer.json:8`)

#### `factureEmise(int $totalTtc): void`

**Signature** : ligne 21  
**Paramètre** : `$totalTtc` — montant TTC en francs CFP entiers

**Implémentation** : lignes 21-24
```php
$this->logger->info('Facture émise', ['total_ttc' => $totalTtc]);
```

**Points critiques** :
- Utilise l'API Monolog 3.x `info()` (conforme à PSR-3 standard — docblock ligne 9)
- Ajoute un contexte Monolog `total_ttc` avec la valeur
- Pas de gestion d'exception — les erreurs Monolog remontent à l'appelant

#### `erreurCalcul(string $message): void`

**Signature** : ligne 26  
**Paramètre** : `$message` — description de l'erreur

**Implémentation** : lignes 26-29
```php
$this->logger->error('Erreur de calcul', ['detail' => $message]);
```

**Points critiques** :
- Utilise l'API Monolog 3.x `error()` (conforme à PSR-3 standard)
- Ajoute un contexte Monolog `detail` avec le message
- Pas de gestion d'exception

### Docblock et déclarations

Ligne 8-10 : docblock indiquant l'utilisation de l'API Monolog 3.x
```php
/**
 * Journalisation applicative — API Monolog 3.x (info/error).
```

Cette déclaration **est importante** : elle documente que le code dépend spécifiquement des méthodes `info()`/`error()` qui sont PSR-3 standard depuis Monolog 3.x (les méthodes `addInfo`/`addError` ont été retirées en Monolog 2.0).

### Dépendances
| Dépendance | Version | Déclaration |
|---|---|---|
| `Monolog` | `^3.0` | `composer.json:8` |

### Risques et dette
| Risque | Localisation | Sévérité | Mitigation |
|---|---|---|---|
| Dépendance à l'API Monolog 3.x | lignes 23,28 | Moyen | Vigilance lors d'une montée vers Monolog 4.x (à dater) ; les méthodes `info()`/`error()` sont standards PSR-3 mais évolution possible |
| Pas d'interface pour injection | lignes 17-18 | Moyen | Extraire une interface `LoggerInterface` |
| Aucun test | (fichier non couvert) | Moyen | Ajouter `AppLoggerTest.php` |
| Pas de gestion d'exception | lignes 23,28 | Faible | Les erreurs Monolog remontent à l'appelant — à documenter |
| Pas de `composer.lock` | (racine) | Moyen | Créer et versionner `composer.lock` |

---

## Dépendances Composer

### Production

| Package | Version déclarée | But | Fichier |
|---|---|---|---|
| `monolog/monolog` | `^3.0` | Journalisation applicative | `src/AppLogger.php` |

**Vigilance** : l'API utilisée (`info`, `error`) appartient à Monolog 3.x et suit le standard PSR-3. La contrainte `^3.0` protège le code contre des versions majeures qui changeraient cette API. Point de vigilance lors d'une montée de version.

### Tests/Développement

| Package | Version déclarée | But | Fichier |
|---|---|---|---|
| `phpunit/phpunit` | `^10.5` | Tests unitaires | `tests/InvoiceCalculatorTest.php` |

**État** : PHPUnit 10.x sans configuration de couverture (`phpunit.xml` ne déclare pas `<coverage>`).

### PHP version minimale

Déclaré en `composer.json:6` : `"php": ">=8.1"`

**Observation** : PHP 8.1 est requis par Monolog 3.x (contrainte de la dépendance). Le code lui-même fonctionne avec PHP 8.0+, mais la chaîne de dépendances impose PHP 8.1 minimum.

---

## Configuration Composer

**Fichier** : `composer.json` — 19 lignes

**Éléments clés** :

| Clé | Valeur | Rôle |
|---|---|---|
| `name` | `shift/pilot-php` | Identifiant package |
| `type` | `project` | Type : projet (pas une bibliothèque) |
| `require` | `php: >=8.1`, `monolog/monolog: ^3.0` | Dépendances production |
| `require-dev` | `phpunit/phpunit: ^10.5` | Dépendances tests |
| `autoload.psr-4` | `App\\` → `src/` | Autoload PSR-4 |
| `autoload-dev.psr-4` | `App\\Tests\\` → `tests/` | Autoload tests |
| `scripts.test` | `phpunit tests` | Commande `composer test` |

**Absence notable** : pas de `composer.lock` (absent du dépôt). Conséquence : deux installations peuvent résoudre des versions différentes de Monolog dans la plage `^3.0`.

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
| Calcul HT | Tous les calculs | `src/InvoiceCalculator.php:21-26` | Validation de clés présente |
| Sélection de taux | TTC au taux correct | `src/InvoiceCalculator.php:48` | Taux mixte supporté (par ligne) |
| Arrondi final | Exactitude du TTC | `src/InvoiceCalculator.php:28,51` | `(int) round()` explicite |
| Construction du logger | Journalisation | `src/AppLogger.php:15-19` | Instanciation directe Monolog |
| API `info()`/`error()` | Événements enregistrés | `src/AppLogger.php:23,28` | Monolog 3.x (PSR-3 standard) |

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
| Tests cas limites partiels | Certains cas non testés | Tableau vide, valeurs négatives : non testés ; débordement et clés manquantes : testés |
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

1. **Montée de version Monolog** — si la contrainte `^3.0` est relâchée vers Monolog 4.x, vérifier que l'API PSR-3 (`info()`, `error()`) reste compatible
2. **Montée de version PHPUnit** — si la contrainte `^10.5` est relâchée vers PHPUnit 11.x, vérifier la compatibilité (PHPUnit 11 requiert PHP >= 8.2)
3. **Taux mixte** — ✅ supporté depuis fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-382 (taux par ligne)
4. **Validation des lignes** — ✅ présente depuis fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-426 (InvalidArgumentException)
5. **Composer.lock** — créer et versionner pour reproductibilité
6. **Couverture de test** — activer `<coverage>` dans `phpunit.xml` et tester `AppLogger`

---

**Branche** : `main` (issue: SHIAAAAAAAAAAAAAAAAAAAAAAAA-426)  
**SHA référence** : `e5a7644` (fix: lever OverflowException si totalHorsTaxe/totalTtc dépasse PHP_INT_MAX)  
**Date de dernière vérification** : 2026-08-06
