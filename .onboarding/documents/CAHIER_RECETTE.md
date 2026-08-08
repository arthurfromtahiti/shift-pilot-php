# Cahier de recette — shift-pilot-php

## Introduction

Ce cahier définit les **critères de recette** permettant de valider que la bibliothèque shift-pilot-php remplit ses engagements fonctionnels et techniques. Il couvre les 12 scénarios de test (testés par la suite PHPUnit, mise à jour 2026-08-08) et les points de vérification d'infrastructure.

**Portée** : la bibliothèque elle-même. L'intégration dans l'application hôte ne relève pas de ce cahier.

**Responsabilité de recette** : le Chef QA ou le propriétaire du projet.

---

## Conditions préalables

### Environnement

- **Système** : Linux ou macOS avec PHP >= 8.1
- **PHP** : version `>=8.1.0`, validé via `php --version`
- **Composer** : installé et opérationnel, validé via `composer --version`
- **Dépendances** : résolvables via `composer install`

### Artefacts à tester

- Dernière version du dépôt sur `origin/main` (branche par défaut)
- SHA cible : `192d0476d7cacc3f7c3b4c5e0c8e8a1e5b7c8d9f` (HEAD post-audit SHIAAAAAAAAAAAAAAAAAAAAAAAA-500)
- Aucune modification locale du code source (`src/`, `tests/`)

### Données de test

Toutes les données sont numériques, en francs CFP entiers. Aucun accès base requis.

---

## Bloc A — Tests nominaux (couvert par PHPUnit)

### Test A1 : Calcul du montant hors taxe (HT)

**Objectif** : vérifier que le calcul HT agrège correctement les produits quantité × prix unitaire

**Cas** : deux lignes `2×10000 + 1×5000 = 25000 F CFP`

**Assertion** :
```php
$calc = new InvoiceCalculator();
$this->assertSame(25000, $calc->totalHorsTaxe([
    ['quantite' => 2, 'prixUnitaire' => 10000],
    ['quantite' => 1, 'prixUnitaire' => 5000],
]));
```

**Test PHPUnit** : `testTotalHorsTaxe` (`tests/InvoiceCalculatorTest.php:16`)  
**Critère de recette** : test passe, assertion exacte (25000), durée < 1s  
**Confiance** : **high** (testé, nominal)

---

### Test A2 : Calcul TTC au taux standard avec taux explicite (16 %)

**Objectif** : vérifier que le calcul TTC applique le taux spécifié par ligne

**Cas** : `HT=10000, taux=0.16 → 10000 × 1.16 = 11600 F CFP`

**Assertion** :
```php
$this->assertSame(11600, $calc->totalTtc([
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.16],
]));
```

**Test PHPUnit** : `testTotalTtcTauxStandard` (`tests/InvoiceCalculatorTest.php:24`)  
**Critère de recette** : test passe, assertion exacte (11600), taux appliqué correctement  
**Confiance** : **high** (testé, nominal)

---

### Test A3 : Calcul TTC au taux réduit avec taux explicite (5 %)

**Objectif** : vérifier que le calcul TTC applique le taux réduit

**Cas** : `HT=10000, taux=0.05 → 10000 × 1.05 = 10500 F CFP`

**Assertion** :
```php
$this->assertSame(10500, $calc->totalTtc([
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.05],
]));
```

**Test PHPUnit** : `testTotalTtcTauxReduit` (`tests/InvoiceCalculatorTest.php:31`)  
**Critère de recette** : test passe, assertion exacte (10500)  
**Confiance** : **high** (testé, nominal)

---

### Test A4 : Calcul TTC à taux mixte (nouvelle capacité)

**Objectif** : vérifier qu'une facture peut mélanger taux standard et taux réduit

**Cas** : ligne 1 à 16 % (11600 F) + ligne 2 à 5 % (10500 F) = 22100 F

**Assertion** :
```php
$this->assertSame(22100, $calc->totalTtc([
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.16],  // 11600
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.05],  // 10500
]));
```

**Test PHPUnit** : `testTotalTtcTauxMixte` (`tests/InvoiceCalculatorTest.php:34`)  
**Critère de recette** : test passe, somme exacte (22100), taux multiples appliqués correctement  
**Confiance** : **high** (testé, limitation d'origine levée)

---

### Test A5 : Taux par défaut si absent

**Objectif** : vérifier que l'absence de clé `taux` replie sur `TGC_STANDARD` (0.16 / 16 %)

**Cas** : pas de clé `taux` → taux par défaut 16 %

**Assertion** :
```php
$this->assertSame(11600, $calc->totalTtc([
    ['quantite' => 1, 'prixUnitaire' => 10000],  // sans 'taux'
]));
```

**Test PHPUnit** : `testTotalTtcSansTauxUtiliseTauxStandard` (`tests/InvoiceCalculatorTest.php:47`)  
**Critère de recette** : test passe, repli sur 16 % confirmé  
**Attention** : ce repli silencieux sans signal d'erreur peut induire une facturation incorrecte si le consommateur omet `taux` pour une ligne qui devrait être à 5 %

---

## Bloc B — Tests d'exception (couvert par PHPUnit)

### Test B1 : Exception clé `quantite` manquante dans HT

**Objectif** : vérifier que l'absence de clé `quantite` lève `\InvalidArgumentException`

**Assertion** :
```php
$this->expectException(\InvalidArgumentException::class);
$calc->totalHorsTaxe([
    ['prixUnitaire' => 10000],  // manque 'quantite'
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:54`  
**Critère de recette** : exception levée, type exact `InvalidArgumentException`

---

### Test B2 : Exception clé `prixUnitaire` manquante dans HT

**Objectif** : vérifier que l'absence de clé `prixUnitaire` lève `\InvalidArgumentException`

**Assertion** :
```php
$this->expectException(\InvalidArgumentException::class);
$calc->totalHorsTaxe([
    ['quantite' => 1],  // manque 'prixUnitaire'
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:62`  
**Critère de recette** : exception levée

---

### Test B3 : Exception clé `quantite` manquante dans TTC

**Objectif** : vérifier que l'absence de clé `quantite` lève `\InvalidArgumentException` dans `totalTtc`

**Assertion** :
```php
$this->expectException(\InvalidArgumentException::class);
$calc->totalTtc([
    ['prixUnitaire' => 10000],  // manque 'quantite'
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:70`  
**Critère de recette** : exception levée

---

### Test B4 : Exception clé `prixUnitaire` manquante dans TTC

**Objectif** : vérifier que l'absence de clé `prixUnitaire` lève `\InvalidArgumentException` dans `totalTtc`

**Assertion** :
```php
$this->expectException(\InvalidArgumentException::class);
$calc->totalTtc([
    ['quantite' => 1],  // manque 'prixUnitaire'
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:78`  
**Critère de recette** : exception levée

---

### Test B5 : Exception ligne sans clé requise

**Objectif** : vérifier qu'une ligne sans aucune clé requise lève `\InvalidArgumentException`

**Assertion** :
```php
$this->expectException(\InvalidArgumentException::class);
$calc->totalTtc([
    [],  // ligne vide
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:86`  
**Critère de recette** : exception levée

---

### Test B6 : Exception dépassement `PHP_INT_MAX` dans HT

**Objectif** : vérifier que le total HT dépassant `PHP_INT_MAX` lève `\OverflowException`

**Cas** : calcul produisant un résultat > `PHP_INT_MAX` (ex. `PHP_INT_MAX + 1`)

**Assertion** :
```php
$this->expectException(\OverflowException::class);
$calc->totalHorsTaxe([
    ['quantite' => (PHP_INT_MAX / 1000) + 1, 'prixUnitaire' => 1000],
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:89`  
**Critère de recette** : exception levée, type exact `OverflowException`

---

### Test B7 : Exception dépassement `PHP_INT_MAX` dans TTC

**Objectif** : vérifier que le total TTC dépassant `PHP_INT_MAX` lève `\OverflowException`

**Cas** : calcul TTC produisant un résultat > `PHP_INT_MAX`

**Assertion** :
```php
$this->expectException(\OverflowException::class);
$calc->totalTtc([
    ['quantite' => (PHP_INT_MAX / 1000) + 1, 'prixUnitaire' => 1000, 'taux' => 0.16],
]);
```

**Test PHPUnit** : `tests/InvoiceCalculatorTest.php:96`  
**Critère de recette** : exception levée

---

## Bloc C — Tests techniques (infrastructure et dépendances)

### Test C1 : Dépendance Monolog 3.x présente et correcte

**Objectif** : vérifier que Monolog 3.x est correctement installée (migré depuis 1.x)

**Étapes** :

1. Exécuter `composer show monolog/monolog`

**Sortie attendue** :
```
monolog/monolog  3.10.0 (ou supérieure dans 3.x)
```

**Critère de recette** :
- ✅ Version majeure = 3
- ✅ Version >= 3.0 (satisfait `^3.0`)
- ✅ `composer.lock` verrouille la version exacte

**Preuve** : `composer.json:8` déclare `^3.0`, `composer.lock` verrouille 3.10.0

**Note** : migration effectuée 2026-08-08, API mises à jour (`info()`/`error()` au lieu de `addInfo()`/`addError()`)

---

### Test C2 : PHP version >= 8.1

**Objectif** : vérifier que l'environnement d'exécution satisfait la contrainte PHP 8.1

**Étapes** :

1. Exécuter `php --version`
2. Extraire le numéro de version majeure.mineure

**Sortie attendue** :
```
PHP 8.1.0 (ou supérieure)
```

**Critère de recette** :
- ✅ Version majeure.mineure >= 8.1

**Preuve** : `composer.json:7` déclare `>=8.1`

**Note** : incohérence détectée — `README.md:7` annonce PHP 8.0, correction nécessaire

---

### Test C3 : Exécution complète de la suite PHPUnit

**Objectif** : valider que l'ensemble des 12 tests s'exécute et que le bootstrap fonctionne

**Étapes** :

1. Nettoyer (optionnel) : `rm -rf vendor/`
2. Installer : `composer install`
3. Exécuter : `composer test` (ou `phpunit` directement)

**Sortie attendue** :
```
PHPUnit 10.5.x (ou supérieure) ...
Tests: 12, Assertions: >= 12, OK.
```

**Critères de recette** :
- ✅ Exit code = 0 (succès)
- ✅ 12 tests exécutés
- ✅ 0 erreurs, 0 failures
- ✅ Durée < 2 secondes

**Preuve** : `phpunit.xml` déclare la testsuite et le bootstrap

---

## Bloc D — Points de vigilance pour évolutions futures

| Point | Priorité | État | Action requise |
|---|---|---|---|
| **Incohérence PHP 8.0 vs 8.1** | Moyen | README.md dit 8.0, composer.json dit 8.1 | Corriger README.md:7 → 8.1 |
| **Incohérence composer.lock** | Moyen | README.md dit « non versionné », fichier est présent | Corriger README.md:13 |
| **`AppLogger` non testé** | Moyen | Zéro test pour cette classe | Ajouter `tests/AppLoggerTest.php` (au min. test de construction) |
| **Taux par défaut silencieux** | Moyen | Absence de `taux` → 16 % sans signal | Documenter dans README.md (risque 16 % au lieu de 5 %) |
| **Taux sans validation de plage** | Faible | Accepte `taux < 0` ou `> 1.0` | Documenter ou valider (`0 ≤ taux ≤ 1.0`) |
| **Facture vide non rejetée** | Faible | `totalTtc([])` = 0 F CFP sans erreur | Clarifier : volontaire ou erreur ? |
| **Pas d'interface `AppLogger`** | Faible | Logger instancié directement (pas d'injection) | Extraire `LoggerInterface` si besoin mock/substitution |

---

## Résumé des résultats attendus

### Bloc A — Tests nominaux (PHPUnit)

```
✓ testTotalHorsTaxe                        PASS
✓ testTotalTtcTauxStandard                 PASS
✓ testTotalTtcTauxReduit                   PASS
✓ testTotalTtcTauxMixte                    PASS
✓ testTotalTtcSansTauxUtiliseTauxStandard  PASS
```

### Bloc B — Tests d'exception (PHPUnit)

```
✓ testTotalHorsTaxeClePrixUnitaireAbsente       PASS
✓ testTotalHorsTaxeCleQuantiteAbsente           PASS
✓ testTotalTtcClePrixUnitaireAbsente            PASS
✓ testTotalTtcCleQuantiteAbsente                PASS
✓ testLigneSansCleMissingAllKeys                PASS
✓ testTotalHorsTaxeOverflowException            PASS
✓ testTotalTtcOverflowException                 PASS
```

### Bloc C — Infrastructure

| Test | Résultat attendu |
|---|---|
| C1 (Monolog version) | 3.10.0 ou supérieure < 4.0 |
| C2 (PHP version) | >= 8.1 |
| C3 (PHPUnit suite) | 12 tests, 0 erreurs, exit 0 |

---

## Résumé global

```
Bloc A (Nominaux)  : 5 tests ✓
Bloc B (Exceptions): 7 tests ✓
Bloc C (Infrastructure) : 3 checks ✓
────────────────────────────────
Total: 12 tests + infrastructure
Verdict: PASS (si tous les critères sont met)
```

---

## Protocole de certification

**Signature** :

- **Testeur** : _________________
- **Date** : _________________
- **Bloc A (tests nominaux)** : ✅ pass / ❌ fail
- **Bloc B (tests d'exception)** : ✅ pass / ❌ fail
- **Bloc C (infrastructure)** : ✅ pass / ❌ fail
- **Verdict global** : ✅ Recette OK / ❌ Recette refusée

**Notes** :

_Espace libre pour documenter les résultats détaillés ou les actions futures_

---

**Branche** : `main`  
**SHA référence** : `192d0476d7cacc3f7c3b4c5e0c8e8a1e5b7c8d9f` (HEAD post-audit SHIAAAAAAAAAAAAAAAAAAAAAAAA-500)  
**Date de dernière mise à jour** : 2026-08-08  
**Audits de référence** : TESTING_AUDIT.md (SHA 7ef6351)  
**Tests source** : `tests/InvoiceCalculatorTest.php` (12 tests)
