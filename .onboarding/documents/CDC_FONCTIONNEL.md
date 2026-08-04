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
- Accepte une liste de lignes au format `{label: string, quantite: int, prixUnitaire: int}`
- Retourne le total HT (somme des `quantite × prixUnitaire`)
- Retourne le total TTC en appliquant un taux TGC sélectionné (standard 16 % ou réduit 5 %)
- Arrondit le résultat final au franc CFP entier

**Limitations** :
- Applique un taux unique à toutes les lignes
- Ne valide pas les entrées (pas de garde sur clés manquantes, pas de rejet de valeurs négatives)
- Retourne 0 F CFP si la liste de lignes est vide (comportement non documenté comme erreur)

### Acteur système : `App\AppLogger`

**Rôle** : enregistre les événements de facturation via Monolog 1.x.

**Capacités** :
- Reçoit la notification « facture émise » avec le montant TTC
- Reçoit la notification « erreur de calcul » avec le message détaillé
- Écrit les entrées sur un canal Monolog configurable (défaut : `facturation`)
- Envoie vers un flux de sortie configurable (défaut : `php://stderr`)

**Limitations** :
- Ne peut pas substituer de handler alternatif sans modification du source
- API Monolog 1.x uniquement (méthodes `addInfo`/`addError`)

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

### Cas 2 : Calcul du TTC au taux standard
L'application hôte appelle `totalTtc()` avec le paramètre par défaut (`$tauxReduit = false`).

```php
$totalTTC = $calc->totalTtc($lignes, false);  // → 29000 F CFP
// Calcul : 25000 × (1 + 0.16) = 29000
```

**Règles appliquées** : R3, R5  
**Preuve** : test `testTotalTtcTauxStandard` retourne `11600` pour HT=10000

### Cas 3 : Calcul du TTC au taux réduit
L'application hôte appelle `totalTtc()` avec le paramètre `$tauxReduit = true`.

```php
$totalTTC = $calc->totalTtc($lignes, true);  // → 26250 F CFP
// Calcul : 25000 × (1 + 0.05) = 26250
```

**Règles appliquées** : R4, R5  
**Preuve** : test `testTotalTtcTauxReduit` retourne `10500` pour HT=10000

### Limitation : Taux TGC unique

La signature `totalTtc(array $lignes, bool $tauxReduit = false)` applique un seul taux à l'ensemble de la facture. Une facture comportant des lignes à taux standard (16 %) et d'autres à taux réduit (5 %) ne peut pas être calculée en un seul appel.

**État** : c'est une **limitation d'API, pas un défaut** — elle était intentionnelle à la conception du pilote.

## Règles métier

### Domaine : Facturation TGC

#### R1 — Ligne de facture
- **Énoncé** : une ligne de facture contient au minimum une quantité et un prix unitaire
- **Format** : tableau associatif PHP avec au minimum les clés `quantite: int, prixUnitaire: int` ; la clé `label` est optionnelle et ignorée
- **Validation** : aucune — accès direct aux clés `quantite` et `prixUnitaire` sans garde
- **Preuve** : `src/InvoiceCalculator.php:19-22` n'accède qu'à `quantite` et `prixUnitaire`
- **Impact sur dev** : l'appelant doit garantir que chaque ligne a au minimum les clés `quantite` et `prixUnitaire`

#### R2 — Montant hors taxe (HT)
- **Énoncé** : le montant HT d'une facture est la somme des produits quantité × prix unitaire pour chaque ligne
- **Formule** : `HT = Σ(quantite × prixUnitaire)`
- **Unité** : francs CFP entiers
- **Preuve** : `src/InvoiceCalculator.php:19-22`, test `testTotalHorsTaxe` = 25000
- **Impact sur dev** : aucune remise, aucune ristourne ne s'applique au niveau ligne — c'est une addition pure

#### R3 — Taux TGC standard
- **Énoncé** : la TGC standard s'applique au taux de 16 %
- **Constante** : `TGC_STANDARD = 0.16` (`src/InvoiceCalculator.php:11`)
- **Condition d'application** : paramètre `$tauxReduit = false` (défaut)
- **Preuve** : `src/InvoiceCalculator.php:11`, test `testTotalTtcTauxStandard`
- **HYPOTHÈSE de conformité** : ce taux correspond aux normes TGC polynésiennes (non sourcé dans le dépôt)

#### R4 — Taux TGC réduit
- **Énoncé** : une TGC réduite au taux de 5 % peut être appliquée à une facture entière
- **Constante** : `TGC_REDUIT = 0.05` (`src/InvoiceCalculator.php:12`)
- **Condition d'application** : paramètre `$tauxReduit = true`
- **Preuve** : `src/InvoiceCalculator.php:12`, test `testTotalTtcTauxReduit`
- **HYPOTHÈSE métier** : ce taux est destiné à certains produits (ex. première nécessité) selon la réglementation TGC polynésienne (non sourcé dans le dépôt)

#### R5 — Montant TTC et arrondi
- **Énoncé** : le montant TTC est calculé en appliquant le taux TGC au HT, puis en arrondissant au franc CFP entier
- **Formule** : `TTC = (int) round(HT × (1 + taux_TGC))`
- **Mode d'arrondi** : `PHP_ROUND_HALF_UP` (défaut PHP sans argument explicite)
- **Preuve** : `src/InvoiceCalculator.php:30`
- **Exemple** : HT=10000 F CFP, taux=16 % → `10000 × 1.16 = 11600 F CFP` exact (test `testTotalTtcTauxStandard`)
- **Exemple** : HT=10000 F CFP, taux=5 % → `10000 × 1.05 = 10500 F CFP` exact (test `testTotalTtcTauxReduit`)
- **HYPOTHÈSE de conformité** : le mode d'arrondi PHP par défaut est conforme à la réglementation TGC CFP (non sourcé dans le dépôt)

#### R6 — Taux unique par facture
- **Énoncé** : une facture ne peut appliquer qu'un seul taux TGC (standard ou réduit) — impossible de panacher sur la même facture
- **Implémentation** : paramètre booléen unique `bool $tauxReduit`, appliqué à tout le total HT
- **Conséquence** : une facture mixte (lignes à 16 % et à 5 %) doit être segmentée et calculée en deux appels
- **Preuve** : `src/InvoiceCalculator.php:26-27` (signature)
- **État** : c'est une limitation d'API, appropriée au scope pilote ; à clarifier pour une évolution production

#### R7 — Pas de remise, pas d'avoir
- **Énoncé** : la bibliothèque calcule un montant brut sans possibilité de remise ou d'avoir
- **Preuve** : aucun paramètre ni clause d'ajustement dans le code (`src/InvoiceCalculator.php`)
- **Impact métier** : les remises doivent être appliquées côté application hôte sur le résultat du TTC

#### R8 — Pas de sous-unité monétaire
- **Énoncé** : les prix sont exprimés en francs CFP entiers — pas de centimes ni de sous-unité
- **Preuve** : tous les tests utilisent des entiers (`25000`, `10000`, `5000`), docblock `src/InvoiceCalculator.php:15`
- **Impact comptable** : tout arrondage se fait au franc entier

### Domaine : Journalisation applicative

#### R9 — Méthode « facture émise »
- **Énoncé** : une méthode `factureEmise()` reçoit le montant TTC et l'enregistre via le logger Monolog
- **Signature** : `factureEmise(int $totalTTC): void`
- **Implémentation** : appelle `$this->logger->addInfo()` avec le contexte `total_ttc`
- **Preuve** : `src/AppLogger.php:21-24`
- **Note** : l'écriture effective des logs dépend de la configuration du handler Monolog côté application hôte

#### R10 — Méthode « erreur de calcul »
- **Énoncé** : une méthode `erreurCalcul()` reçoit un message et l'enregistre en tant que message d'erreur via le logger Monolog
- **Signature** : `erreurCalcul(string $message): void`
- **Implémentation** : appelle `$this->logger->addError()` avec le contexte `detail`
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
- **Preuve** : `src/AppLogger.php:23,28` (appels directs à `$this->logger->addInfo()` / `addError()`)
- **Impact dev** : le formatage des logs relève de la configuration Monolog côté application hôte

## Données

### Structure de facture en mémoire

**Pas de persistance** — les factures n'existent que pendant l'exécution, en mémoire PHP.

**Format de ligne de facture** :
```php
[
    'label'        => string,    // Libellé du produit/service
    'quantite'     => int,        // Nombre d'unités
    'prixUnitaire' => int         // Prix en francs CFP par unité
]
```

**Aucune autre clé n'est reconnue** — toute clé supplémentaire sera ignorée ; l'absence d'une clé causera une erreur à l'exécution.

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

**Preuve** : logique de `src/InvoiceCalculator.php:19-22`

**Ambiguïté métier** : une facture sans lignes est-elle un cas valide (0 F CFP) ou une erreur à signaler ? Non documenté.

**État** : `HYPOTHÈSE` — un développeur qui rencontre ce cas n'a pas de directive claire.

### Clé manquante dans une ligne

**Observation** : si une ligne n'a pas la clé `quantite` ou `prixUnitaire`, l'accès direct causera une erreur.

**Comportement** : PHP génère un avertissement (`Warning: Undefined array key "quantite"`) et retourne `null`, qui se coerce en 0 dans le calcul.

**Conséquence** : le calcul produit un résultat incorrect (HT inférieur au réel) sans signal d'erreur explicite.

**Preuve** : aucun test ne couvre ce cas ; `src/InvoiceCalculator.php:21` accède directement sans validation

**État** : `HYPOTHÈSE` — ce comportement runtime n'a pas été observé à l'exécution dans ce run.

### Valeur négative (quantité ou prix)

**Observation** : rien n'empêche une ligne d'avoir une quantité ou un prix négatif.

**Comportement** : le calcul produit un HT négatif (ex. `1 × (-5000) = -5000 F CFP`).

**Cas d'usage métier** : avoirs (factures de retour/remboursement) peuvent théoriquement être représentés ainsi.

**État** : `HYPOTHÈSE` — ce cas est supposé supporté, mais n'est pas documenté ni testé.

**Impact** : si les valeurs négatives ne sont pas volontaires, elles passent silencieusement sans erreur.

### Taux TGC mixte sur la même facture

Voir **R6** ci-dessus — limitation d'API, pas un cas à couvrir.

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
- Que les factures mixtes (taux multiples) soient supportées — elles ne le sont pas par l'API

---

**Branche** : `main`  
**SHA référence** : `5f5c8ee00765beb04be08b5bcb089066c36a0f30`  
**Date de dernière vérification** : 2026-08-04
