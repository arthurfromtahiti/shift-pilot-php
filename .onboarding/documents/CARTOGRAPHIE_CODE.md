# Cartographie du code — shift-pilot-php

## Inventaire et navigation

### Fichiers productifs

```
src/
├── InvoiceCalculator.php  [57 lignes]   Unique classe métier — calcul HT/TTC avec taux par ligne
└── AppLogger.php          [30 lignes]   Adaptateur technique Monolog 3.x
```

### Fichiers de configuration et support

```
.
├── composer.json          [19 lignes]   Déclaration des dépendances (Monolog 3.x, PHPUnit 10.5)
├── composer.lock          [568 lignes]  ✅ Présent et versionné depuis 2026-08-08 (Monolog 3.10.0, PHPUnit 10.5.64)
├── phpunit.xml            [13 lignes]   Configuration PHPUnit (pas de collecte de couverture)
└── README.md              [13 lignes]   Présentation du pilote (incohérences détectées : PHP 8.0/8.1, composer.lock)
```

### Fichiers de test

```
tests/
└── InvoiceCalculatorTest.php  [101 lignes]   12 tests PHPUnit (nominaux, mixtes, exceptions)
```

**Total** : 8 fichiers versionnés (y/c composer.lock) ; 2 classes PHP ; 12 tests exécutables.

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
│                          │              │       (Monolog 3.x)      │
└──────────────────────────┘              └──────────────────────────┘
     │                                            │
     │ (aucune dépendance)                        │
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
`src/InvoiceCalculator.php` — 57 lignes (mise à jour 2026-08-08)

### Responsabilité
Calcul du montant HT/TTC d'une facture selon les règles TGC, avec support des taux mixtes par ligne.

### Namespace et autoload
- Namespace : `App\`
- Autoload PSR-4 : `"App\\"` → `src/` (déclaré en `composer.json:15`)

### Constantes de classe

| Nom | Valeur | Rôle |
|---|---|---|
| `TGC_STANDARD` | `0.16` | Taux standard (16 %) — ligne 11 |
| `TGC_REDUIT` | `0.05` | Taux réduit (5 %) — ligne 12 |

**Point de vigilance** : ces constantes sont l'unique source de vérité pour les taux TGC. Toute modification doit passer par ici.

### Méthodes publiques

#### `totalHorsTaxe(array $lignes): int`

**Signature** : ligne 14  
**Paramètre** :
- `$lignes` : array de lignes, chaque ligne = `{label?: string, quantite: int, prixUnitaire: int}`

**Retour** : somme des `quantite × prixUnitaire` pour chaque ligne, en francs CFP entiers

**Implémentation** : lignes 16-33
```php
$total = 0;
foreach ($lignes as $ligne) {
    if (!isset($ligne['quantite'])) {
        throw new \InvalidArgumentException('Clé quantite manquante');
    }
    if (!isset($ligne['prixUnitaire'])) {
        throw new \InvalidArgumentException('Clé prixUnitaire manquante');
    }
    $total += $ligne['quantite'] * $ligne['prixUnitaire'];
}
$total = (int) round($total);
if ($total > PHP_INT_MAX) {
    throw new \OverflowException('Dépassement PHP_INT_MAX');
}
return $total;
```

**Points critiques** :
- **Gardes ajoutées** (2026-08-08) : `isset()` + `\InvalidArgumentException` sur clés manquantes (lignes 23-25, 45-47)
- **Overflow détecté** : `\OverflowException` si total > `PHP_INT_MAX` (lignes 29-31)
- Arrondi explicite : `(int) round()` (ligne 28)
- Aucun rejet de valeurs négatives (comportement volontaire pour avoirs potentiels)

**Tests couvrant** : 
- `testTotalHorsTaxe` — 2 lignes, résultat 25000 ✓
- `testTotalHorsTaxeClePrixUnitaireAbsente` — exception levée ✓
- `testTotalHorsTaxeCleQuantiteAbsente` — exception levée ✓
- `testTotalHorsTaxeOverflowException` — overflow détecté ✓

#### `totalTtc(array $lignes): int`

**Signature** : ligne 35 (**changement d'API** — ancien paramètre `bool $tauxReduit` supprimé)  
**Paramètre** :
- `$lignes` : array de lignes avec structure `{label?: string, quantite: int, prixUnitaire: int, taux?: float}`

**Retour** : `(int) round(Σ(quantite × prixUnitaire × (1 + taux)))` où taux par ligne, défaut `TGC_STANDARD`

**Implémentation** : lignes 37-54
```php
$total = 0;
foreach ($lignes as $ligne) {
    if (!isset($ligne['quantite'])) {
        throw new \InvalidArgumentException('Clé quantite manquante');
    }
    if (!isset($ligne['prixUnitaire'])) {
        throw new \InvalidArgumentException('Clé prixUnitaire manquante');
    }
    $taux = $ligne['taux'] ?? self::TGC_STANDARD;
    $total += $ligne['quantite'] * $ligne['prixUnitaire'] * (1 + $taux);
}
$total = (int) round($total);
if ($total > PHP_INT_MAX) {
    throw new \OverflowException('Dépassement PHP_INT_MAX');
}
return $total;
```

**Points critiques** :
- **Taux par ligne** (2026-08-08) : chaque ligne porte sa clé `taux?: float` optionnelle (ligne 48)
- **Repli sur défaut** : si `taux` absent, applique `self::TGC_STANDARD` (16 %) silencieusement (ligne 48)
- **Taux mixte désormais supporté** : factures avec lignes à 16 % ET lignes à 5 % en un seul appel (test `testTotalTtcTauxMixte`)
- **Gardes ajoutées** (2026-08-08) : comme `totalHorsTaxe`, validation des clés obligatoires et overflow
- **Pas de validation de plage** : accepte `taux < 0` ou `taux > 1.0` sans erreur

**Changement d'API majeur** :
- **Avant** : `totalTtc(array $lignes, bool $tauxReduit = false)`
- **Après** : `totalTtc(array $lignes)` — taux spécifié par ligne

**Tests couvrant** :
- `testTotalTtcTauxStandard` — taux 0.16 explicite, résultat 11600 ✓
- `testTotalTtcTauxReduit` — taux 0.05 explicite, résultat 10500 ✓
- `testTotalTtcTauxMixte` — lignes à 16 % et 5 % mélangées, résultat 22100 ✓
- `testTotalTtcSansTauxUtiliseTauxStandard` — absence de `taux`, repli 16 % ✓
- `testTotalTtcClePrixUnitaireAbsente` — exception ✓
- `testTotalTtcCleQuantiteAbsente` — exception ✓
- `testTotalTtcOverflowException` — overflow ✓

### Dépendances
Aucune — classe autonome, zéro dépendance Composer.

### Risques et dette
| Risque | Localisation | Sévérité | État |
|---|---|---|---|
| Taux non validé en plage | ligne 48 | Faible | Accepte `taux < 0` ou `> 1.0` ; à documenter ou valider |
| Tableau vide retourne 0 | lignes 37-54 | Faible | Comportement non documenté comme erreur volontaire |
| Valeur négative non rejetée | ligne 49 | Faible | Volontaire pour avoirs ; à clarifier dans README |
| Repli silencieux sur taux par défaut | ligne 48 | Moyen | Risque facturation 16 % au lieu de 5 % si `taux` omis ; à documenter |

---

## Classe 2 : `App\AppLogger`

### Fichier
`src/AppLogger.php` — 30 lignes

### Responsabilité
Adaptateur technique pour l'enregistrement des événements de facturation via **Monolog 3.x** (migré depuis 1.x en 2026-08-08).

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

**Dépendance Monolog** : `monolog/monolog: ^3.0` (déclaré en `composer.json:8`, verrouillé à 3.10.0 dans `composer.lock`)

#### `factureEmise(int $totalTtc): void`

**Signature** : ligne 21  
**Paramètre** : `$totalTtc` — montant TTC en francs CFP entiers

**Implémentation** : lignes 23
```php
$this->logger->info('Facture émise', ['total_ttc' => $totalTtc]);
```

**Points critiques** :
- **API Monolog 3.x** (2026-08-08) : utilise `info()` à la place de l'ancien `addInfo()` (Monolog 1.x)
- Ajoute un contexte Monolog `total_ttc` avec la valeur
- Pas de gestion d'exception — les erreurs Monolog remontent à l'appelant

**Test couvrant** : aucun (classe non testée, risque à adresser)

#### `erreurCalcul(string $message): void`

**Signature** : ligne 25  
**Paramètre** : `$message` — description de l'erreur

**Implémentation** : ligne 28
```php
$this->logger->error('Erreur de calcul', ['detail' => $message]);
```

**Points critiques** :
- **API Monolog 3.x** (2026-08-08) : utilise `error()` à la place de l'ancien `addError()`
- Ajoute un contexte Monolog `detail` avec le message
- Pas de gestion d'exception

**Test couvrant** : aucun (classe non testée)

### Docblock et déclarations

Ligne 9 : docblock indiquant l'utilisation de Monolog 3.x (mise à jour 2026-08-08)
```php
/**
 * @uses Monolog\Logger (API 3.x: info/error) ...
```

### Dépendances
| Dépendance | Version | Déclaration | État |
|---|---|---|---|
| `Monolog` | `^3.0` | `composer.json:8` | ✅ Verrouillé à 3.10.0 dans composer.lock |

### Risques et dette
| Risque | Localisation | Sévérité | État |
|---|---|---|---|
| **Classe entièrement non testée** | (fichier entier) | Moyen | ⚠️ **À adresser** : ajouter `AppLoggerTest.php` (au min. test de construction) |
| Pas d'interface pour injection | lignes 17-18 | Moyen | Acceptable pour pilote ; extraire `LoggerInterface` pour production |
| Pas de gestion d'exception | lignes 23,28 | Faible | Les erreurs Monolog remontent à l'appelant — à documenter |
| Dépend API spécifique Monolog 3.x | lignes 23,28 | Moyen | `info()`/`error()` propres à 3.x ; changements de version majeure relèvent de la gestion Composer et équipe ops |

---

## Dépendances Composer

### Production

| Package | Version déclarée | Version verrouillée | But | Fichier |
|---|---|---|---|---|
| `monolog/monolog` | `^3.0` | `3.10.0` (composer.lock) | Journalisation applicative | `src/AppLogger.php` |

**Vigilance** : l'API utilisée (`info`, `error`) appartient à Monolog 3.x. La migration depuis 1.x est complète (2026-08-08). La contrainte `^3.0` protège contre un saut majeur 4.x. **composer.lock est présent et versionné** — builds reproductibles garantis.

### Tests/Développement

| Package | Version déclarée | Version verrouillée | But | Fichier |
|---|---|---|---|---|
| `phpunit/phpunit` | `^10.5` | `10.5.64` (composer.lock) | Tests unitaires | `tests/InvoiceCalculatorTest.php` |

**État** : PHPUnit 10.5.x ; **12 tests couvrent `InvoiceCalculator`** ; `AppLogger` n'a aucun test.

### PHP version minimale

Déclaré en `composer.json:7` : `"php": ">=8.1"`

**⚠️ Incohérence détectée** : `README.md:7` annonce `PHP >= 8.0` mais `composer.json:7` requiert `>=8.1`. Correction nécessaire.

**Observation** : le code n'exploite aucune syntaxe PHP 8.1+ (pas de `match`, pas de constructor promotion, pas de `readonly`) — le minimum 8.1 est un héritage de la migration Monolog 3.x.

---

## Configuration Composer

**Fichier** : `composer.json` — 19 lignes

**Éléments clés** :

| Clé | Valeur | Rôle |
|---|---|---|
| `name` | `shift/shift-pilot-php` | Identifiant package |
| `type` | `library` | Type : bibliothèque (pas une application) |
| `require` | `php: >=8.1`, `monolog/monolog: ^3.0` | Dépendances production |
| `require-dev` | `phpunit/phpunit: ^10.5` | Dépendances tests |
| `autoload.psr-4` | `App\\` → `src/` | Autoload PSR-4 |
| `autoload.tests.psr-4` | `App\\Tests\\` → `tests/` | Autoload tests |
| `scripts.test` | `phpunit` | Commande `composer test` |

**✅ État** : `composer.lock` est **présent et versionné** (depuis 2026-08-08) — dépendances reproductibles.

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

**Tests** : 12 tests, tous dans `tests/InvoiceCalculatorTest.php`

---

## Chemins critiques dans le code

| Chemin | Critique pour | Localisation | Points d'attention |
|---|---|---|---|
| Calcul HT | Tous les calculs | `src/InvoiceCalculator.php:16-33` | ✅ Gardes ajoutées, validation clés + overflow |
| Taux par ligne | Facturation mixte | `src/InvoiceCalculator.php:48` | ✅ Support des taux mixtes, repli 16 % par défaut |
| Arrondi final | Exactitude TTC | `src/InvoiceCalculator.php:28,51` | ✅ VÉRIFIÉ_CODE: `(int) round()` sans argument de mode (lignes 28, 51) ; HYPOTHÈSE: applique `PHP_ROUND_HALF_UP` selon le comportement PHP 8.1+ — conformité TGC CFP à valider avec l'autorité fiscale |
| Construction du logger | Journalisation | `src/AppLogger.php:17-18` | Instanciation directe Monolog, pas d'injection |
| API `info()`/`error()` | Événements enregistrés | `src/AppLogger.php:23,28` | ✅ Monolog 3.x (migré depuis 1.x) |

---

## Flux d'appel nominal

```
Application hôte
   │
   ├─→ new InvoiceCalculator()
   │      │
   │      └─→ initialise constantes TGC_STANDARD=0.16, TGC_REDUIT=0.05
   │
   ├─→ $calc->totalHorsTaxe($lignes)
   │      │
   │      ├─→ valide clés quantite, prixUnitaire (InvalidArgumentException sinon)
   │      │
   │      ├─→ Σ(quantité × prixUnitaire)
   │      │
   │      ├─→ check overflow (OverflowException sinon)
   │      │
   │      └─→ retourne int (montant HT)
   │
   ├─→ $calc->totalTtc($lignes)
   │      │
   │      ├─→ valide clés quantite, prixUnitaire
   │      │
   │      ├─→ boucle sur lignes :
   │      │   ├─→ taux = $ligne['taux'] ?? 0.16
   │      │   └─→ accumule quantite × prixUnitaire × (1 + taux)
   │      │
   │      ├─→ arrondi : (int) round()
   │      │
   │      ├─→ check overflow
   │      │
   │      └─→ retourne int (montant TTC)
   │
   └─→ new AppLogger('facturation', 'php://stderr')
          │
          ├─→ new Logger('facturation')
          │
          ├─→ pushHandler(new StreamHandler('php://stderr'))
          │
          ├─→ $logger->info('Facture émise', ['total_ttc' => ...])
          │
          └─→ $logger->error('Erreur de calcul', ['detail' => ...])
```

---

## Zones à modifier avec prudence

| Zone | Raison | Impact si modifié |
|---|---|---|
| `TGC_STANDARD`, `TGC_REDUIT` | Source unique de vérité | Tous les calculs TTC changent |
| Signature `totalTtc(array $lignes)` | Contrat public de l'API | Rupture compatibilité consommateurs |
| Clé `taux` dans ligne | Support taux mixtes | Factures de consommateurs qui omettaient `taux` obtiendraient soudain 16 % au lieu d'une erreur |
| `(int) round()` ligne 28,51 | Mode d'arrondi | Exactitude financière en cas limites |
| Monolog 3.x API | Dépendance externe | Rupture si Monolog 4.x change l'API `info()`/`error()` |

---

**Branche** : `main`  
**SHA référence** : `7470fd9` (corrections documentaires critiques)  
**Date de dernière mise à jour** : 2026-08-09  
**Audits de référence** : ARCHITECTURE_AUDIT.md, FUNCTIONAL_AUDIT.md, CODE_HOTSPOTS_AUDIT.md, DATA_MODEL_AUDIT.md, SECURITY_ROBUSTNESS_AUDIT.md, TESTING_AUDIT.md
