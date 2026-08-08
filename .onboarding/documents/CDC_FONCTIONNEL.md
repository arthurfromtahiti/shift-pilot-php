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
- Ne peut pas remplacer le logger Monolog sans modifier la classe `AppLogger`
- Ne peut pas accéder aux entités persistées (aucune persistance dans ce dépôt)

### Acteur système : `App\InvoiceCalculator`

**Rôle** : calcule les montants TTC selon les règles métier de la TGC.

**Capacités** :
- Accepte une liste de lignes au format `{label?: string, quantite: int, prixUnitaire: int, taux?: float}`
- Retourne le total HT (somme des `quantite × prixUnitaire`)
- Retourne le total TTC en appliquant le taux TGC spécifié par ligne (ou `TGC_STANDARD` 16 % en repli)
- **Supporte les taux mixtes** : chaque ligne peut avoir un taux différent ; une facture peut mélanger lignes à 16 % et lignes à 5 %
- Valide les entrées : lève `\InvalidArgumentException` si `quantite` ou `prixUnitaire` est absent ; `\OverflowException` si le total dépasse `PHP_INT_MAX`
- Arrondit le résultat final au franc CFP entier

**Limitations** :
- Le taux par ligne, s'il est absent, replie silencieusement sur `TGC_STANDARD` (16 %) — aucun signal d'erreur ou avertissement
- Retourne 0 F CFP si la liste de lignes est vide (comportement non documenté comme erreur)
- Ne valide pas la plage du taux (accepte `taux < 0` ou `taux > 1.0` sans erreur)

**Gardes d'entrée ajoutées (2026-08-08)** :
- `\InvalidArgumentException` si la clé `quantite` ou `prixUnitaire` est absente d'une ligne (`src/InvoiceCalculator.php:23-25`, `45-47`)
- `\OverflowException` si le total HT ou TTC dépasse `PHP_INT_MAX` (`src/InvoiceCalculator.php:29-31`, `52-54`)

### Acteur système : `App\AppLogger`

**Rôle** : enregistre les événements de facturation via Monolog 3.x.

**Capacités** :
- Reçoit la notification « facture émise » avec le montant TTC
- Reçoit la notification « erreur de calcul » avec le message détaillé
- Écrit les entrées sur un canal Monolog configurable (défaut : `facturation`)
- Envoie vers un flux de sortie configurable (défaut : `php://stderr`)
- **API Monolog 3.x** : utilise les méthodes modernes `info()` et `error()`

**Limitations** :
- Ne peut pas substituer de handler alternatif sans modification du source (aucune injection de dépendance)
- Aucun test n'existe pour cette classe — régression de dépendance Monolog non interceptée

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

### Cas 2 : Calcul du TTC au taux standard par ligne
L'application hôte prépare une ligne avec un taux explicite et appelle `totalTtc()`.

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.16]  // 10000 × 1.16 = 11600
]
$totalTTC = $calc->totalTtc($lignes);  // → 11600 F CFP
```

**Règles appliquées** : R3, R5  
**Preuve** : test `testTotalTtcTauxStandard` retourne `11600` pour HT=10000 avec taux explicite 0.16

### Cas 3 : Calcul du TTC au taux réduit par ligne
L'application hôte spécifie un taux réduit dans la ligne.

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.05]  // 10000 × 1.05 = 10500
]
$totalTTC = $calc->totalTtc($lignes);  // → 10500 F CFP
```

**Règles appliquées** : R4, R5  
**Preuve** : test `testTotalTtcTauxReduit` retourne `10500` pour HT=10000 avec taux explicite 0.05

### Cas 4 : Calcul du TTC à taux mixte (nouvelle capacité)
L'application hôte calcule une facture avec des lignes à taux différents.

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.16],  // 11600
    ['quantite' => 1, 'prixUnitaire' => 10000, 'taux' => 0.05]   // 10500
]
$totalTTC = $calc->totalTtc($lignes);  // → 22100 F CFP
```

**Règles appliquées** : R3, R4, R5  
**Preuve** : test `testTotalTtcTauxMixte` retourne `22100` (somme `11600 + 10500`)

### Cas 5 : Taux par défaut si absent
Si une ligne n'inclut pas de clé `taux`, le taux standard 16 % est appliqué.

```php
$lignes = [
    ['quantite' => 1, 'prixUnitaire' => 10000]  // sans 'taux' → repli sur TGC_STANDARD (0.16)
]
$totalTTC = $calc->totalTtc($lignes);  // → 11600 F CFP
```

**Règles appliquées** : R3, R5  
**Preuve** : test `testTotalTtcSansTauxUtiliseTauxStandard` retourne `11600` sans clé `taux`  
**Attention** : ce repli silencieux (16 % par défaut) peut induire une facturation incorrecte si le consommateur omet volontairement le taux pour une ligne qui devrait être à 5 % — risque documenté mais non guaranti par l'API.

## Règles métier

### Domaine : Facturation TGC

#### R1 — Ligne de facture
- **Énoncé** : une ligne de facture contient au minimum une quantité et un prix unitaire
- **Format** : tableau associatif PHP avec au minimum les clés `quantite: int, prixUnitaire: int` ; la clé `label` est optionnelle et ignorée ; la clé `taux` est optionnelle (défaut `TGC_STANDARD` 0.16)
- **Validation** : validation stricte — accès aux clés `quantite` et `prixUnitaire` avec garde `\InvalidArgumentException` si absentes
- **Preuve** : `src/InvoiceCalculator.php:23-25` valide `quantite` et `prixUnitaire`, lève `\InvalidArgumentException` si absentes
- **Impact sur dev** : les clés obligatoires doivent être présentes ; une ligne mal formée lève une exception, ne produit pas un calcul incorrect

#### R2 — Montant hors taxe (HT)
- **Énoncé** : le montant HT d'une facture est la somme des produits quantité × prix unitaire pour chaque ligne
- **Formule** : `HT = Σ(quantite × prixUnitaire)`
- **Unité** : francs CFP entiers
- **Preuve** : `src/InvoiceCalculator.php:21-26`, test `testTotalHorsTaxe` = 25000
- **Impact sur dev** : aucune remise, aucune ristourne ne s'applique au niveau ligne — c'est une addition pure

#### R3 — Taux TGC standard
- **Énoncé** : la TGC standard s'applique au taux de 16 % par défaut si la ligne ne spécifie pas de taux
- **Constante** : `TGC_STANDARD = 0.16` (`src/InvoiceCalculator.php:11`)
- **Condition d'application** : clé `taux` absente de la ligne — repli silencieux sur 16 %
- **Preuve** : `src/InvoiceCalculator.php:11`, test `testTotalTtcTauxStandard` et `testTotalTtcSansTauxUtiliseTauxStandard`
- **HYPOTHÈSE de conformité** : ce taux correspond aux normes TGC polynésiennes (non sourcé dans le dépôt)

#### R4 — Taux TGC réduit (par ligne)
- **Énoncé** : une TGC réduite au taux de 5 % peut être appliquée à une ou plusieurs lignes via la clé `taux`
- **Constante** : `TGC_REDUIT = 0.05` (`src/InvoiceCalculator.php:12`)
- **Condition d'application** : spécification explicite `taux: 0.05` dans la ligne
- **Preuve** : `src/InvoiceCalculator.php:12`, test `testTotalTtcTauxReduit` et `testTotalTtcTauxMixte`
- **HYPOTHÈSE métier** : ce taux est destiné à certains produits (ex. première nécessité) selon la réglementation TGC polynésienne (non sourcé dans le dépôt)

#### R5 — Montant TTC et arrondi
- **Énoncé** : le montant TTC est calculé en appliquant le taux TGC à chaque ligne individuellement, puis en accumulant les résultats et en arrondissant une seule fois au franc CFP entier
- **Formule** : `TTC = (int) round( Σ(quantite × prixUnitaire × (1 + taux_par_ligne)) )`
- **Mode d'arrondi** : `round()` sans argument explicite — utilise `PHP_ROUND_HALF_UP` selon le comportement standard PHP 8.1+
- **Preuve** : `src/InvoiceCalculator.php:43-51` (accumulation sur variable `float` intermédiaire `$total` puis un appel unique à `round()`)
- **Exemple** : HT=10000 F CFP, taux=16 % → `10000 × 1.16 = 11600 F CFP` exact (test `testTotalTtcTauxStandard`)
- **Exemple** : HT=10000 F CFP, taux=5 % → `10000 × 1.05 = 10500 F CFP` exact (test `testTotalTtcTauxReduit`)
- **Conséquence pour taux mixte** : une facture avec des lignes à 16 % et à 5 % accumule sans arrondir entre les lignes, puis arrondit une fois au final : `(11600.0 + 10500.0) = 22100.0 → round(22100.0) = 22100` (test `testTotalTtcTauxMixte`)
- **HYPOTHÈSE** : le mode d'arrondi `PHP_ROUND_HALF_UP` et le pattern d'accumulation sans arrondi intermédiaire respectent la réglementation TGC CFP — à valider auprès de l'autorité fiscale polynésienne (non sourcé dans le dépôt)

#### R6 — Taux par ligne (depuis mise à jour 2026-08-08)
- **Énoncé** : chaque ligne de facture peut spécifier son propre taux TGC via la clé optionnelle `taux?: float`
- **Implémentation** : `src/InvoiceCalculator.php:48` — `$taux = $ligne['taux'] ?? self::TGC_STANDARD`
- **Repli par défaut** : si la clé `taux` est absente, le taux standard `TGC_STANDARD` (0.16 / 16 %) s'applique
- **Conséquence** : une facture mixte (lignes à 16 % et à 5 %) peut désormais être calculée en un seul appel en spécifiant `taux` par ligne
- **Preuve** : `src/InvoiceCalculator.php:41` (signature `totalTtc(array $lignes): int`), test `testTotalTtcTauxMixte` = 22100 (11600 + 10500)
- **État** : limitation d'origine **levée** — la capacité taux mixte est désormais implémentée et testée

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
- **Implémentation** : appelle `$this->logger->info()` (API Monolog 3.x, mise à jour 2026-08-08) avec le contexte `total_ttc`
- **Preuve** : `src/AppLogger.php:23`
- **Note** : l'écriture effective des logs dépend de la configuration du handler Monolog côté application hôte

#### R10 — Méthode « erreur de calcul »
- **Énoncé** : une méthode `erreurCalcul()` reçoit un message et l'enregistre en tant que message d'erreur via le logger Monolog
- **Signature** : `erreurCalcul(string $message): void`
- **Implémentation** : appelle `$this->logger->error()` (API Monolog 3.x, mise à jour 2026-08-08) avec le contexte `detail`
- **Preuve** : `src/AppLogger.php:28`
- **Note** : l'écriture effective des logs dépend de la configuration du handler Monolog côté application hôte

#### R11 — Canal de sortie configurable
- **Énoncé** : la destination des logs (canal Monolog et fichier/flux) doit être configurable à la construction du logger
- **Défaut** : canal `facturation`, flux `php://stderr` (sortie d'erreur standard)
- **Configuration** : paramètres du constructeur `__construct(string $canal = 'facturation', string $fichier = 'php://stderr')`
- **Preuve** : `src/AppLogger.php:15-18`
- **Impact opérationnel** : permettre à l'application hôte de rediriger les logs selon son infrastructure (fichier, syslog, API, etc.)

#### R12 — Aucun traitement du message en sortie
- **Énoncé** : la bibliothèque transmet les messages directement à Monolog sans filtrage ni enrichissement supplémentaire
- **Implémentation** : `src/AppLogger.php:23,28` effectue des appels directs à `$this->logger->info()` / `error()` avec le contexte fourni (clé `total_ttc` pour émission, clé `detail` pour erreur)
- **API** : utilise Monolog 3.x (méthodes `info()` et `error()` modernes)
- **Limitation** : l'écriture effective des logs (destination, formatage, niveau de sévérité) dépend entièrement de la configuration des handlers Monolog côté application hôte — ce dépôt ne configure que le canal et le flux par défaut
- **Preuve** : `src/AppLogger.php:15-18` (constructeur configurable, défaut canal `facturation` et flux `php://stderr`)
- **Impact dev** : le formatage et la persistance des logs relèvent de la configuration Monolog côté application hôte ; `AppLogger` ne les contrôle pas

## Données

### Structure de facture en mémoire

**Pas de persistance** — les factures n'existent que pendant l'exécution, en mémoire PHP.

**Format de ligne de facture** :
```php
[
    'label'        => string,    // Libellé du produit/service (optionnel, ignoré)
    'quantite'     => int,        // Nombre d'unités (REQUIS)
    'prixUnitaire' => int,        // Prix en francs CFP par unité (REQUIS)
    'taux'         => float       // Taux TGC appliqué (0.16, 0.05, autre ; optionnel, défaut TGC_STANDARD 0.16)
]
```

**Clés obligatoires** : `quantite`, `prixUnitaire`  
**Clés optionnelles** : `label` (ignorée), `taux` (défaut 0.16 si absente)  
**Clés supplémentaires** : toute autre clé sera ignorée ; l'absence de clé obligatoire causera une `\InvalidArgumentException` à l'exécution.

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

**Comportement** : `src/InvoiceCalculator.php:23-25` (dans `totalHorsTaxe`) et `src/InvoiceCalculator.php:45-47` (dans `totalTtc`) vérifient l'existence de ces deux clés via `isset()` et lèvent explicitement l'exception avec le message « Chaque ligne doit contenir "quantite" et "prixUnitaire" ».

**Conséquence** : une ligne mal formée provoque un arrêt explicite avec un message d'erreur clair.

**Preuve** : `src/InvoiceCalculator.php:23-25,45-47` ; tests `tests/InvoiceCalculatorTest.php:54-87` couvrent ces cas

**État** : `VÉRIFIÉ_CODE` — ce comportement est garanti et testé.

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
2. **Calcul TTC** : `totalTtc(...)` retourne `(int) round( Σ(quantite × prixUnitaire × (1 + taux_par_ligne)) )` où chaque ligne peut spécifier son propre taux ou replie sur `TGC_STANDARD`
3. **Validation d'entrée** : `totalHorsTaxe` et `totalTtc` lèvent `\InvalidArgumentException` si `quantite` ou `prixUnitaire` est absent, `\OverflowException` si le résultat dépasse `PHP_INT_MAX`
4. **Taux mixte** : une facture avec des lignes à taux différents (16 % et 5 %) peut être calculée en un seul appel avec accumulation sans arrondi intermédiaire
5. **Journalisation — signature** : `factureEmise(int $totalTTC): void` et `erreurCalcul(string $message): void` existent et peuvent être appelées sans lever d'exception au niveau du code de cette classe
6. **Journalisation — intégration** : `AppLogger` transmet les événements à Monolog 3.x via les méthodes `info()` et `error()` ; l'écriture effective (destination, sérialisation, formatage) dépend de la configuration des handlers Monolog côté application hôte et n'est pas garantie par cette classe
7. **Arrondi** : l'arrondi final utilise `round()` sans argument explicite de mode — applique `PHP_ROUND_HALF_UP` selon le comportement standard PHP 8.1+

## Limites de garantie

La bibliothèque **ne garantit pas** :
- Que les taux TGC (16 %, 5 %) sont conformes à la réglementation polynésienne actuelle (à valider avec le board)
- Que le mode d'arrondi `PHP_ROUND_HALF_UP` (appliqué par `round()` sans argument) est conforme à la réglementation TGC CFP (à valider avec le board/autorité fiscale)
- Que le logger Monolog 3.x reste accessible ou que sa version majeure suivante ne cassera pas l'API `info()`/`error()`
- Que la facture vide retourne 0 F CFP par design plutôt que par défaut du langage (à clarifier si besoin)
- Que la plage du taux fourni par ligne (par ex. `taux: -0.5` ou `taux: 2.0`) produise un résultat valide — toute valeur est acceptée sans validation
- Que le comportement du taux par défaut silencieux (16 % si absent) ne causera pas d'erreur de facturation chez le consommateur — c'est un risque documenté

---

**Branche** : `main`  
**SHA référence** : `a3bc97a` (HEAD courant, documents de référence mis à jour)  
**Date de dernière mise à jour** : 2026-08-08  
**Audits de référence** : ARCHITECTURE_AUDIT.md, FUNCTIONAL_AUDIT.md, CODE_HOTSPOTS_AUDIT.md, DATA_MODEL_AUDIT.md, SECURITY_ROBUSTNESS_AUDIT.md, TESTING_AUDIT.md
