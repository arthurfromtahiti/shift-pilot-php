# Cahier de recette — shift-pilot-php

## Introduction

Ce cahier définit les **critères de recette** permettant de valider que la bibliothèque shift-pilot-php remplit ses engagements fonctionnels et techniques. Il couvre les scénarios nominaux (testés par la suite PHPUnit) et les points de vérification d'infrastructure.

**Portée** : la bibliothèque elle-même. L'intégration dans l'application hôte ne relève pas de ce cahier.

**Responsabilité de recette** : le Chef QA ou le propriétaire du projet.

---

## Conditions préalables

### Environnement

- **Système** : Linux ou macOS avec PHP >= 8.0
- **PHP** : version `>=8.0.0`, validé via `php --version`
- **Composer** : installé et opérationnel, validé via `composer --version`
- **Dépendances** : résolvables via `composer install`

### Artefacts à tester

- Dernière version du dépôt sur `origin/main` (branche par défaut)
- SHA cible : `e5a7644` (dernier commit : "fix: lever OverflowException si totalHorsTaxe/totalTtc dépasse PHP_INT_MAX")
- Aucune modification locale du code source (`src/`, `tests/`)

### Données de test

Toutes les données sont numériques, en francs CFP entiers. Aucun accès base requis.

---

## Bloc A — Tests d'exécution (couvert par PHPUnit)

Ces cas sont validés par la suite de tests existante. **Devant tous passer au vert.**

### Test A1 : Calcul du montant hors taxe (HT)

**Objectif** : vérifier que le calcul HT agrège correctement les produits quantité × prix unitaire

**Préconditions** :
- Dépôt cloné et dépendances installées : `composer install`
- Fichier test présent : `tests/InvoiceCalculatorTest.php`

**Étapes** :

1. Exécuter la suite : `composer test`
2. Localiser le test `testTotalHorsTaxe` dans la sortie
3. Vérifier la ligne de test : `testTotalHorsTaxe` (ligne 10)

**Assertions attendues** :
```php
// Deux lignes : 2×10000 + 1×5000 = 25000 F CFP
$calc = new InvoiceCalculator();
$this->assertSame(25000, $calc->totalHorsTaxe([
    ['label' => 'Produit A', 'quantite' => 2, 'prixUnitaire' => 10000],
    ['label' => 'Produit B', 'quantite' => 1, 'prixUnitaire' => 5000],
]));
```

**Critère de recette** :
- ✅ Test `testTotalHorsTaxe` passe (exit code 0)
- ✅ Assertion `assertSame(25000, ...)` ne lève pas d'exception
- ✅ Durée de test < 1 seconde

**Preuve** : `tests/InvoiceCalculatorTest.php:10`

**Confiance** : **high** (testé, nominal)

---

### Test A2 : Calcul du TTC au taux standard (16 %)

**Objectif** : vérifier que le calcul TTC applique correctement la TGC standard

**Étapes** :

1. Exécuter `composer test`
2. Localiser le test `testTotalTtcTauxStandard` dans la sortie

**Assertion attendue** :
```php
// HT=10000 F CFP, taux=16 % → TTC = 10000 × 1.16 = 11600 F CFP
$this->assertSame(11600, $calc->totalTtc([
    ['label' => 'Produit', 'quantite' => 1, 'prixUnitaire' => 10000, 'taux' => InvoiceCalculator::TGC_STANDARD],
]));
```

**Calcul justifié** : `(int) round(10000 × 1.16) = 11600` (pas d'arrondi intermédiaire requis ici)

**Critère de recette** :
- ✅ Test `testTotalTtcTauxStandard` passe
- ✅ Assertion `assertSame(11600, ...)` exacte (pas d'écart ±1)

**Preuve** : `tests/InvoiceCalculatorTest.php:20`

**Confiance** : **high** (testé, nominal)

---

### Test A3 : Calcul du TTC au taux réduit (5 %)

**Objectif** : vérifier que le calcul TTC applique correctement la TGC réduite

**Étapes** :

1. Exécuter `composer test`
2. Localiser le test `testTotalTtcTauxReduit` dans la sortie

**Assertion attendue** :
```php
// HT=10000 F CFP, taux=5 % → TTC = 10000 × 1.05 = 10500 F CFP
$this->assertSame(10500, $calc->totalTtc([
    ['label' => 'Produit première nécessité', 'quantite' => 1, 'prixUnitaire' => 10000, 'taux' => InvoiceCalculator::TGC_REDUIT],
]));
```

**Calcul justifié** : `(int) round(10000 × 1.05) = 10500`

**Critère de recette** :
- ✅ Test `testTotalTtcTauxReduit` passe
- ✅ Assertion `assertSame(10500, ...)` exacte

**Preuve** : `tests/InvoiceCalculatorTest.php:27`

**Confiance** : **high** (testé, nominal)

---

### Test A4 : Levée d'exception sur débordement HT

**Objectif** : vérifier que `totalHorsTaxe()` lève `\OverflowException` si le total dépasse `PHP_INT_MAX`

**Étapes** :

1. Exécuter la suite : `composer test`
2. Localiser le test `testTotalHorsTaxeLèveOverflowExceptionSiDépassementPhpIntMax` dans la sortie

**Assertion attendue** :
```php
// Valeur qui dépasse PHP_INT_MAX quand multipliée
$this->expectException(\OverflowException::class);
$calc = new InvoiceCalculator();
$calc->totalHorsTaxe([
    ['label' => 'Overflow', 'quantite' => PHP_INT_MAX, 'prixUnitaire' => 2],
]);
```

**Critère de recette** :
- ✅ Exception `\OverflowException` levée avec message contenant « PHP_INT_MAX »
- ✅ Pas de valeur erronée retournée

**Preuve** : `tests/InvoiceCalculatorTest.php:89`

**Confiance** : **high** (cas limite critique)

---

### Test A5 : Levée d'exception sur débordement TTC

**Objectif** : vérifier que `totalTtc()` lève `\OverflowException` si le total dépasse `PHP_INT_MAX`

**Étapes** :

1. Exécuter la suite : `composer test`
2. Localiser le test `testTotalTtcLèveOverflowExceptionSiDépassementPhpIntMax` dans la sortie

**Assertion attendue** :
```php
// Total qui déborde même après application du taux
$this->expectException(\OverflowException::class);
$calc = new InvoiceCalculator();
$calc->totalTtc([
    ['label' => 'Overflow', 'quantite' => 100000000000, 'prixUnitaire' => 100000000000],
]);
```

**Critère de recette** :
- ✅ Exception `\OverflowException` levée avec message contenant « PHP_INT_MAX »

**Preuve** : `tests/InvoiceCalculatorTest.php:96`

**Confiance** : **high** (cas limite critique)

---

### Test A6 : Levée d'exception sur clé manquante

**Objectif** : vérifier que les deux méthodes lèvent `\InvalidArgumentException` si une ligne manque `quantite` ou `prixUnitaire`

**Étapes** :

1. Exécuter `composer test`
2. Localiser les tests de validation

**Assertion attendue** :
```php
$calc = new InvoiceCalculator();
$this->expectException(\InvalidArgumentException::class);
$calc->totalHorsTaxe([
    ['label' => 'Produit incomplet'],  // pas de 'quantite' ni 'prixUnitaire'
]);
```

**Critère de recette** :
- ✅ Exception `\InvalidArgumentException` levée avec message approprié

**Preuve** : `tests/InvoiceCalculatorTest.php` lignes 54–86 (5 tests couvrant ce cas)

**Confiance** : **high** (validé à l'exécution — tests dont les noms commencent par `testTotalHorsTaxeLigneS*Exception` et `testTotalTtcLigneS*Exception`)

---

### Test A7 : Exécution de la suite PHPUnit complète

**Objectif** : valider que tous les tests s'exécutent et que le bootstrap fonctionne

**Étapes** :

1. Nettoyer les caches : `rm -rf vendor/` (optionnel)
2. Installer les dépendances : `composer install`
3. Exécuter la suite : `composer test` (ou `phpunit` directement)

**Sortie attendue** :
```
PHPUnit 10.x.x ...
Tests: 12, Assertions: 12+, OK.
```

**Critères de recette** :
- ✅ Exit code = 0 (succès)
- ✅ 12 tests exécutés (nominaux + exceptions + cas limites)
- ✅ 0 erreurs, 0 failures

**Preuve** : `phpunit.xml` déclare la testsuite et le bootstrap

**Confiance** : **high** (techniquement testable)

---

## Bloc C — Tests techniques (infrastructure et dépendances)

### Test C1 : Dépendance Monolog présente et correcte

**Objectif** : vérifier que Monolog 3.x est correctement installée

**Étapes** :

1. Exécuter `composer show` ou `composer show monolog/monolog`
2. Vérifier la version

**Sortie attendue** :
```
monolog/monolog  3.0.0 (ou supérieure, <4.0)
```

**Critère de recette** :
- ✅ Version majeure = 3 (pas de Monolog 1.x, 2.x ni 4.x+)
- ✅ Version >= 3.0 (mineure satisfait `^3.0`)

**Preuve** : `composer.json:8` déclare `^3.0`

**Confiance** : **high** (contrôlable techniquement)

---


### Test C2 : PHP version >= 8.1

**Objectif** : vérifier que l'environnement d'exécution satisfait la contrainte

**Étapes** :

1. Exécuter `php --version`
2. Extraire le numéro de version majeure et mineure

**Sortie attendue** :
```
PHP 8.1.0 (ou supérieure)
```

**Critère de recette** :
- ✅ Version >= 8.1 (Monolog 3.x requiert PHP >= 8.1)

**Preuve** : `composer.json:6` déclare `>=8.1`

**Confiance** : **high** (vérifiable simplement)

---

## Bloc D — Points de vigilance pour évolutions futures

| Point | Critère | État | Action requise |
|---|---|---|---|
| Point | Priorité | Note |
|---|---|---|
| **Pas de `composer.lock`** | Moyen | Créer et versionner pour reproductibilité |
| **`AppLogger` non testé** | Moyen | Ajouter `tests/AppLoggerTest.php` si l'évolution justifie la couverture |
| **Pas de `<coverage>` en PHPUnit** | Moyen | Configurer en `phpunit.xml` si une mesure de couverture devient utile |
| **Montée de version Monolog** | Moyen | Vérifier la compatibilité de l'API avant toute montée majeure |
| **Pas d'interface `AppLogger`** | Faible | Extraire `LoggerInterface` si le besoin de mock/substitution émerge |

---

## Résumé des résultats attendus

### Bloc A — Tests d'exécution (PHPUnit)

```
✓ testTotalHorsTaxe                                               PASS
✓ testTotalTtcTauxStandard                                        PASS
✓ testTotalTtcTauxReduit                                          PASS
✓ testTotalTtcTauxMixte                                           PASS
✓ testTotalTtcSansTauxUtiliseTauxStandard                         PASS
✓ testTotalHorsTaxeLigneSansPrixUnitaireLèveException             PASS
✓ testTotalHorsTaxeLigneSansQuantiteLèveException                PASS
✓ testTotalTtcLigneSansPrixUnitaireLèveException                 PASS
✓ testTotalTtcLigneSansQuantiteLèveException                     PASS
✓ testTotalHorsTaxeLigneSansAucuneClésRequises                   PASS
✓ testTotalHorsTaxeLèveOverflowExceptionSiDépassementPhpIntMax   PASS
✓ testTotalTtcLèveOverflowExceptionSiDépassementPhpIntMax        PASS
────────────────────────────────────────────────────────────────────
Tests : 12, Assertions : 12+
Failures : 0, Errors : 0
```

**Exit code** : 0

### Bloc C — Infrastructure

| Test | Résultat attendu |
|---|---|
| C1 (Monolog version) | 3.0 ou supérieure < 4.0 |
| C2 (PHP version) | >= 8.1 |

---

## Protocole de certification

**Signature** :

- **Testeur** : _________________
- **Date** : _________________
- **Bloc A (tests unitaires)** : ✅ pass / ❌ fail
- **Bloc C (infrastructure)** : ✅ pass / ❌ fail
- **Verdict global** : ✅ Recette OK / ❌ Recette refusée

**Notes** :

_Espace libre pour documenter les résultats détaillés ou les actions futures_

---

**Branche** : `main` (issue: SHIAAAAAAAAAAAAAAAAAAAAAAAA-426)  
**SHA référence** : `e5a7644` (fix: lever OverflowException si totalHorsTaxe/totalTtc dépasse PHP_INT_MAX)  
**Date de dernière vérification** : 2026-08-06
