# Cahier des charges fonctionnel — shift-pilot-php

## Contexte métier

La bibliothèque shift-pilot-php répond à un **besoin comptable de facturation avec la TGC** (taxe générale sur la consommation, Polynésie française). Toute structure commerciale opérant en Polynésie française qui émet des factures doit en calculer le total en appliquant le bon taux de TGC selon la nature des produits vendus.

Le dépôt porte la qualification « pilote de test » : il implémente le **sous-ensemble minimal** permettant de valider le processus d'onboarding, sans aspirer à couvrir la gamme complète des cas d'usage commerciaux réels.

## Acteurs et capacités

### Acteur : Application hôte (consommatrice de la bibliothèque)

**Rôle** : instancie les deux classes publiques (`InvoiceCalculator`, `AppLogger`), construit les structures de facture, appelle le calcul et traite les résultats.

**Capacités** :
- Fournir une liste de lignes de facture (label, quantité, prix unitaire)
- Appeler le calcul HT seul ou le calcul TTC avec sélection du taux
- Consulter les montants retournés (entiers en francs CFP)
- Enregistrer les événements de facturation sur un logger configuré

**Limitations** :
- Ne peut pas calculer une facture mixte (lignes à 16 % et lignes à 5 % dans le même appel)
- Ne peut pas remplacer le logger Monolog sans modifier la classe `AppLogger`
- Ne peut pas accéder aux entités persistées (aucune persistance dans ce dépôt)

### Acteur système : `App\InvoiceCalculator`

**Rôle** : calcule les montants TTC selon les règles métier de la TGC.

**Capacités** :
- `totalHorsTaxe(array $lignes): int` — accepte une liste de lignes au format `{label?: string, quantite: int, prixUnitaire: int}`, retourne le total HT
- `totalTtc(array $lignes): int` — accepte une liste de lignes au format `{label?: string, quantite: int, prixUnitaire: int, taux?: float}`, retourne le total TTC avec application des taux par ligne
- Supporte les taux TGC mixtes : chaque ligne peut spécifier son propre taux via la clé `taux` (défaut : `TGC_STANDARD = 0.16`)
- Arrondit le résultat final au franc CFP entier

**Exceptions levées** :
- `\InvalidArgumentException` : si une ligne manque les clés `quantite` ou `prixUnitaire`
- `\OverflowException` : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN`

**Comportements** :
- Ne valide pas les valeurs négatives (quantités/prix négatifs sont acceptés pour les avoirs)
- Retourne 0 F CFP si la liste de lignes est vide (comportement non documenté comme erreur)

### Acteur système : `App\AppLogger`

**Rôle** : enregistre les événements de facturation via Monolog 3.x.

**Capacités** :
- Reçoit la notification « facture émise » avec le montant TTC
- Reçoit la notification « erreur de calcul » avec le message détaillé
- Écrit les entrées sur un canal Monolog configurable (défaut : `facturation`)
- Envoie vers un flux de sortie configurable (défaut : `php://stderr`)

**Limitations** :
- Ne peut pas substituer de handler alternatif sans modification du source
- API Monolog 3.x (méthodes PSR-3 `info()`/`error()`)

## Parcours utilisateur et cas testés

### Cas 1 : Calcul du total hors taxe
L'application hôte prépare une liste de lignes et appelle `totalHorsTaxe()`.

```php
$lignes = [
    ['quantite' => 2, 'prixUnitaire' => 10000],  // 20000 F CFP
    ['quantite' => 1, 'prixUnitaire' => 5000],   // 5000 F CFP
]
$totalHT = $calc->totalHorsTaxe($lignes);  // → 25000 F CFP
```

**Règle appliquée** : R2 — somme des produits `quantite × prixUnitaire`  
**Preuve** : test `testTotalHorsTaxe` retourne `25000`

### Cas 2 : Calcul du TTC avec taux standard
L'application hôte appelle `totalTtc()` avec des lignes où chaque ligne peut spécifier son propre taux TGC, ou utiliser le défaut (taux standard 16 %).

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000],  // Utilise TGC_STANDARD par défaut
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.16],  // Explicitement standard
];
$totalTTC = $calc->totalTtc($lignes);  // → 23200 F CFP
// Calcul : (10000×1.16) + (10000×1.16) = 23200
```

**Règles appliquées** : R3, R5  
**Preuve** : test `testTotalTtcTauxStandard` retourne `11600` pour HT=10000 à 16 %

### Cas 3 : Calcul du TTC avec taux réduit
L'application hôte appelle `totalTtc()` avec une ligne spécifiant le taux réduit via la clé `taux`.

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.05],  // Taux réduit
];
$totalTTC = $calc->totalTtc($lignes);  // → 10500 F CFP
// Calcul : 10000 × 1.05 = 10500
```

**Règles appliquées** : R4, R5  
**Preuve** : test `testTotalTtcTauxReduit` retourne `10500` pour HT=10000 à 5 %

### Support : Taux mixte sur la même facture

La signature `totalTtc(array $lignes)` accepte à présent que chaque ligne porte son propre taux TGC via la clé optionnelle `taux`. Une facture comportant des lignes à 16 % et d'autres à 5 % peut désormais être calculée en un seul appel.

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.16],  // Standard
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.05],  // Réduit
];
$totalTTC = $calc->totalTtc($lignes);  // → 22100 F CFP
// Calcul : (10000 × 1.16) + (10000 × 1.05) = 11600 + 10500 = 22100
```

**État** : cette **capacité de taux par ligne est nouvelle** — elle remplace l'ancienne API booléenne `$tauxReduit`.

## Règles métier

### Domaine : Facturation TGC

#### R1 — Ligne de facture
- **Énoncé** : une ligne de facture contient au minimum une quantité et un prix unitaire
- **Format** : tableau associatif PHP avec au minimum les clés `quantite: int, prixUnitaire: int` ; la clé `label` est optionnelle et ignorée
- **Validation** : vérification explicite de la présence des clés `quantite` et `prixUnitaire` ; l'absence lève `\InvalidArgumentException`
- **Preuve** : `src/InvoiceCalculator.php:23,45` vérifie `!isset($ligne['quantite'], $ligne['prixUnitaire'])` avant accès
- **Impact sur dev** : l'appelant doit garantir que chaque ligne a au minimum les clés `quantite` et `prixUnitaire`, ou traiter l'exception

#### R2 — Montant hors taxe (HT)
- **Énoncé** : le montant HT d'une facture est la somme des produits quantité × prix unitaire pour chaque ligne
- **Formule** : `HT = Σ(quantite × prixUnitaire)`
- **Unité** : francs CFP entiers
- **Preuve** : `src/InvoiceCalculator.php:21-26`, test `testTotalHorsTaxe` = 25000
- **Impact sur dev** : aucune remise, aucune ristourne ne s'applique au niveau ligne — c'est une addition pure

#### R3 — Taux TGC standard
- **Énoncé** : la TGC standard s'applique au taux de 16 %
- **Constante** : `TGC_STANDARD = 0.16` (`src/InvoiceCalculator.php:11`)
- **Condition d'application** : défaut utilisé par chaque ligne qui ne spécifie pas de clé `taux`
- **Preuve** : `src/InvoiceCalculator.php:11`, test `testTotalTtcTauxStandard`, test `testTotalTtcSansTauxUtiliseTauxStandard`
- **HYPOTHÈSE de conformité** : ce taux correspond aux normes TGC polynésiennes (non sourcé dans le dépôt)

#### R4 — Taux TGC réduit
- **Énoncé** : une TGC réduite au taux de 5 % peut être appliquée par ligne via la clé `taux`
- **Constante** : `TGC_REDUIT = 0.05` (`src/InvoiceCalculator.php:12`)
- **Condition d'application** : chaque ligne peut spécifier `'taux' => 0.05` (ou une autre valeur)
- **Preuve** : `src/InvoiceCalculator.php:12`, test `testTotalTtcTauxReduit`, test `testTotalTtcTauxMixte`
- **HYPOTHÈSE métier** : ce taux est destiné à certains produits (ex. première nécessité) selon la réglementation TGC polynésienne (non sourcé dans le dépôt)

#### R5 — Montant TTC et arrondi
- **Énoncé** : le montant TTC est calculé en appliquant le taux TGC à chaque ligne, puis en arrondissant la somme au franc CFP entier
- **Formule** : `TTC = (int) round(Σ(quantite_i × prixUnitaire_i × (1 + taux_i)))`
- **Mode d'arrondi** : `PHP_ROUND_HALF_UP` (défaut PHP sans argument explicite)
- **Preuve** : `src/InvoiceCalculator.php:43-51`
- **Exemple** : HT=10000 F CFP, taux=16 % → `10000 × 1.16 = 11600 F CFP` exact (test `testTotalTtcTauxStandard`)
- **Exemple** : HT=10000 F CFP, taux=5 % → `10000 × 1.05 = 10500 F CFP` exact (test `testTotalTtcTauxReduit`)
- **Exemple mixte** : (10000 × 1.16) + (10000 × 1.05) = 22100 F CFP (test `testTotalTtcTauxMixte`)
- **HYPOTHÈSE de conformité** : le mode d'arrondi PHP par défaut est conforme à la réglementation TGC CFP (non sourcé dans le dépôt)

#### R6 — Taux par ligne (support des taux mixtes)
- **Énoncé** : chaque ligne de facture peut spécifier son propre taux TGC via la clé optionnelle `taux`
- **Implémentation** : accès `$ligne['taux'] ?? self::TGC_STANDARD` — défaut au taux standard si absent
- **Conséquence** : une facture mixte (lignes à 16 % et à 5 %) peut être calculée en un seul appel
- **Preuve** : `src/InvoiceCalculator.php:48` (taux par ligne)
- **État** : capacité nouvelle (depuis fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-426) ; remplace l'ancienne API booléenne

#### R7 — Pas de remise, pas d'avoir
- **Énoncé** : la bibliothèque calcule un montant brut sans possibilité de remise ou d'avoir
- **Preuve** : aucun paramètre ni clause d'ajustement dans le code (`src/InvoiceCalculator.php`)
- **Impact métier** : les remises doivent être appliquées côté application hôte sur le résultat du TTC

#### R8 — Pas de sous-unité monétaire
- **Énoncé** : les prix sont exprimés en francs CFP entiers — pas de centimes ni de sous-unité
- **Preuve** : tous les tests utilisent des entiers (`25000`, `10000`, `5000`), docblock `src/InvoiceCalculator.php:15`
- **Impact comptable** : tout arrondage se fait au franc entier

#### R13 — Validation des clés de ligne
- **Énoncé** : chaque ligne doit contenir au minimum les clés `quantite` et `prixUnitaire` — l'absence levée `\InvalidArgumentException`
- **Implémentation** : vérification `!isset($ligne['quantite'], $ligne['prixUnitaire'])` avant accès
- **Preuve** : `src/InvoiceCalculator.php:23, 45`
- **Impact dev** : l'application hôte doit garantir la structure des lignes ou gérer l'exception

#### R14 — Détection du débordement d'entier (OverflowException)
- **Énoncé** : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN`, une `\OverflowException` est levée au lieu de retourner une valeur erronée silencieusement
- **Implémentation** : vérification post-arrondi `if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) { throw ... }`
- **Preuve** : `src/InvoiceCalculator.php:29-30, 52-53`
- **Impact dev** : les appelants doivent prévoir le traitement de `\OverflowException` pour les grandes factures
- **Contexte** : résout le problème d'arrondi silencieux décrit en SHIAAAAAAAAAAAAAAAAAAAAAAAA-421

### Domaine : Journalisation applicative

#### R9 — Méthode « facture émise »
- **Énoncé** : une méthode `factureEmise()` reçoit le montant TTC et l'enregistre via le logger Monolog
- **Signature** : `factureEmise(int $totalTTC): void`
- **Implémentation** : appelle `$this->logger->info()` avec le contexte `total_ttc`
- **Preuve** : `src/AppLogger.php:21-24`
- **Note** : l'écriture effective des logs dépend de la configuration du handler Monolog côté application hôte

#### R10 — Méthode « erreur de calcul »
- **Énoncé** : une méthode `erreurCalcul()` reçoit un message et l'enregistre en tant que message d'erreur via le logger Monolog
- **Signature** : `erreurCalcul(string $message): void`
- **Implémentation** : appelle `$this->logger->error()` avec le contexte `detail`
- **Preuve** : `src/AppLogger.php:26-29`
- **Note** : l'écriture effective des logs dépend de la configuration du handler Monolog côté application hôte

#### R11 — Canal de sortie configurable
- **Énoncé** : la destination des logs (canal Monolog et fichier/flux) doit être configurable à la construction du logger
- **Défaut** : canal `facturation`, flux `php://stderr` (sortie d'erreur standard)
- **Configuration** : paramètres du constructeur `__construct(string $canal = 'facturation', string $fichier = 'php://stderr')`
- **Preuve** : `src/AppLogger.php:15-18`
- **Impact opérationnel** : permettre à l'application hôte de rediriger les logs selon son infrastructure (fichier, syslog, API, etc.)

#### R12 — Aucun traitement du message en sortie
- **Énoncé** : la bibliothèque ne filtre, n'enrichit ni ne formate les messages — elle les transmet tels quels à Monolog
- **Preuve** : `src/AppLogger.php:23,28` (appels directs à `$this->logger->info()` / `error()`)
- **Impact dev** : le formatage des logs relève de la configuration Monolog côté application hôte

## Données

### Structure de facture en mémoire

**Pas de persistance** — les factures n'existent que pendant l'exécution, en mémoire PHP.

**Format de ligne de facture** :
```php
[
    'label'        => string,    // Libellé du produit/service (optionnel, ignoré)
    'quantite'     => int,       // ⚠️ REQUIS — Nombre d'unités
    'prixUnitaire' => int,       // ⚠️ REQUIS — Prix en francs CFP par unité
    'taux'         => float      // Optionnel — Taux TGC de la ligne (défaut : TGC_STANDARD = 0.16)
]
```

**Règles** :
- Les clés `quantite` et `prixUnitaire` sont **obligatoires** — l'absence lève `\InvalidArgumentException`
- La clé `taux` est **optionnelle** — si absente, défaut au taux standard (0.16)
- Toute clé supplémentaire (autre que les 4 ci-dessus) sera ignorée

### Constantes de domaine

| Nom | Valeur | Localisation |
|---|---|---|
| `TGC_STANDARD` | `0.16` | `src/InvoiceCalculator.php:11` |
| `TGC_REDUIT` | `0.05` | `src/InvoiceCalculator.php:12` |

Ces constantes sont l'**unique point source de vérité** pour les deux taux utilisés par la bibliothèque. Toute modification doit passer par ces deux lignes. Le contexte d'application de ces taux (produits généraux vs première nécessité) relève de la réglementation TGC polynésienne, à valider externement.

## Cas limites et comportements non documentés

### Tableau vide `[]`

**Observation** : `totalHorsTaxe([])` retourne `0` ; `totalTtc([])` retourne `0`.

**Comportement** : la boucle ne s'exécute pas, l'accumulateur reste 0.

**Preuve** : logique de `src/InvoiceCalculator.php:21-26`

**Ambiguïté métier** : une facture sans lignes est-elle un cas valide (0 F CFP) ou une erreur à signaler ? Non documenté.

**État** : `HYPOTHÈSE` — un développeur qui rencontre ce cas n'a pas de directive claire.

### Clé manquante dans une ligne

**Observation** : si une ligne n'a pas la clé `quantite` ou `prixUnitaire`, une `\InvalidArgumentException` est levée.

**Comportement** : vérification préalable `!isset($ligne['quantite'], $ligne['prixUnitaire'])` avant accès (ligne 23, 45).

**Conséquence** : l'application hôte doit gérer l'exception ou garantir la structure des entrées.

**Preuve** : `src/InvoiceCalculator.php:23-24, 45-46` ; implémentation explicite de la validation

**État** : ✅ RÉSOLU — le code valide à présent les clés requis et lève une exception explicite (depuis fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-426).

### Valeur négative (quantité ou prix)

**Observation** : rien n'empêche une ligne d'avoir une quantité ou un prix négatif.

**Comportement** : le calcul produit un HT négatif (ex. `1 × (-5000) = -5000 F CFP`).

**Cas d'usage métier** : avoirs (factures de retour/remboursement) peuvent théoriquement être représentés ainsi.

**État** : `HYPOTHÈSE` — ce cas est supposé supporté, mais n'est pas documenté ni testé.

**Impact** : si les valeurs négatives ne sont pas volontaires, elles passent silencieusement sans erreur.

### Taux TGC mixte sur la même facture

Voir **R6** ci-dessus — cette capacité est à présent supportée via la clé `taux` par ligne.

### Débordement d'entier (PHP_INT_MAX / PHP_INT_MIN)

**Observation** : si le total calculé dépasse `PHP_INT_MAX` ou descend sous `PHP_INT_MIN`, une `\OverflowException` est levée.

**Comportement** : vérification post-arrondi `if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) { throw ... }` (lignes 29-30, 52-53).

**Conséquence** : l'application hôte doit gérer l'exception pour les calculs susceptibles de générer des montants très élevés.

**Preuve** : `src/InvoiceCalculator.php:29-31, 52-54` ; tests `testTotalHorsTaxeLèveOverflowExceptionSiDépassementPhpIntMax`, `testTotalTtcLèveOverflowExceptionSiDépassementPhpIntMax` (lignes 89, 96)

**État** : ✅ RÉSOLU — le code détecte et signale à présent le débordement au lieu de retourner une valeur erronée (depuis fix/SHIAAAAAAAAAAAAAAAAAAAAAAAA-426, résout SHIAAAAAAAAAAAAAAAAAAAAAAAA-421).

## Critères d'acceptation (pivot de livraison)

Un appel à une fonction de la bibliothèque est **fonctionnellement correct** si et seulement si :

1. **Calcul HT** : `totalHorsTaxe(...)` retourne la somme exacte de tous les produits `quantite × prixUnitaire`
2. **Calcul TTC** : `totalTtc(...)` retourne `(int) round(HT × (1 + taux))` où le taux est celui sélectionné
3. **Journalisation** : `factureEmise(...)` et `erreurCalcul(...)` ne lèvent pas d'exception et enregistrent sur le logger Monolog
4. **Arrondi** : l'arrondi final suit la règle PHP native `round()` sans argument de mode

## Limites de garantie

La bibliothèque **ne garantit pas** :
- Que les taux TGC (16 %, 5 %) sont conformes à la réglementation polynésienne actuelle (à valider avec le board)
- Que le mode d'arrondi PHP par défaut est conforme à la réglementation (à valider avec le board)
- Que le logger Monolog reste accessible ou que sa version ne cassera pas lors de montées de version
- Que la facture vide retourne 0 F CFP par design plutôt que par défaut du langage (à clarifier si besoin)

## Ce qui est à présent garanti

✅ **Détection du débordement** : les totaux qui dépassent `PHP_INT_MAX` ou descendent sous `PHP_INT_MIN` lèvent une exception explicite `\OverflowException` au lieu de retourner silencieusement une valeur erronée.

✅ **Validation des lignes** : chaque ligne est vérifiée pour la présence de `quantite` et `prixUnitaire` ; l'absence lève une `\InvalidArgumentException` explicite.

✅ **Support des taux mixtes** : chaque ligne peut spécifier son propre taux TGC via la clé `taux`, permettant les factures à taux multiples en un seul appel.

---

**Branche** : `main` (issue: SHIAAAAAAAAAAAAAAAAAAAAAAAA-426)  
**SHA référence** : `e5a7644` (fix: lever OverflowException si totalHorsTaxe/totalTtc dépasse PHP_INT_MAX)  
**Date de dernière vérification** : 2026-08-06
