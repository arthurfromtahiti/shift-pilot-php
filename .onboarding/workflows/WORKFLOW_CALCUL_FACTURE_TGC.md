# WORKFLOW_CALCUL_FACTURE_TGC — Calcul HT et TTC d'une facture avec TGC

> **Réconciliation** — run SHIAAAAAAAAAAAAAAAAAAAAAAAA-498 (2026-08-08, SHA `6d4f877`).
> La version précédente avait été produite sur un code antérieur (paramètre `$tauxReduit` unique par facture). Depuis, plusieurs dérives ont été corrigées : taux par ligne (CLA-250), `InvalidArgumentException` (CLA-293/CLA-280), `OverflowException` (SHIAAAAAAAAAAAAAAAAAAAAAAAA-426). Les sections concernées ont été mises à jour ; voir *Réconciliation* en fin de document.

## Classification
- **Type** : business_process
- **Sous-type** : calcul monétaire avec taxe sectorielle
- **Visibilité** : technical
- **Acteur principal** : application consommatrice de la bibliothèque
- **Acteurs** : code appelant PHP, `App\InvoiceCalculator`
- **Criticité** : Haute — seule fonction métier du dépôt ; sans ce calcul, la bibliothèque n'a pas de raison d'être
- **Confiance** : medium (chemin nominal et cas d'exception clés couverts ; cas limites non testés : valeurs négatives, liste vide, taux arbitraires, différence d'arrondi par ligne vs arrondi unique — PHP absent de l'environnement, tests non exécutés, toutes les affirmations sont `VÉRIFIÉ_CODE`)
- **Justification** : les deux méthodes publiques (`totalHorsTaxe`, `totalTtc`) ont été lues intégralement (`src/InvoiceCalculator.php`, 57 lignes) ; 12 tests PHPUnit lus dans `tests/InvoiceCalculatorTest.php`. Les tests couvrent les cas nominaux (taux standard, réduit, mixte, par défaut), les clés manquantes (4× `InvalidArgumentException`) et les débordements (2× `OverflowException`) — pas les valeurs négatives, la liste vide, les taux arbitraires ni la différence d'arrondi.

## Objectif
Permettre à une application PHP de calculer le **total hors taxe** puis le **total toutes taxes comprises (TTC)** d'une facture composée de plusieurs lignes, en appliquant la **TGC** (taxe générale sur la consommation, Polynésie française). Chaque ligne porte son propre taux TGC (standard 16 % ou réduit 5 %) ; une même facture peut donc **mélanger des taux**. Les montants sont en **francs CFP entiers**. La bibliothèque ne persiste rien, ne génère aucun document : elle calcule et retourne.

## Acteurs
- **Application consommatrice** : code PHP tiers qui instancie `InvoiceCalculator` et lui fournit les lignes de facture
- **`App\InvoiceCalculator`** (`src/InvoiceCalculator.php`) : agrège les lignes, applique le taux TGC par ligne, retourne un entier en francs CFP

## Points d'entrée
- `App\InvoiceCalculator::totalHorsTaxe(array $lignes): int` — calcul du total HT seul (`src/InvoiceCalculator.php:19`)
- `App\InvoiceCalculator::totalTtc(array $lignes): int` — calcul du total TTC, boucle indépendante de `totalHorsTaxe` (`src/InvoiceCalculator.php:41`)

## Étapes principales

### Chemin A — Calcul du total hors taxe (`totalHorsTaxe`)
1. **Constitution de la liste de lignes** : l'appelant bâtit un tableau `$lignes` ; chaque entrée a la forme `{label: string, quantite: int, prixUnitaire: int}` (docblock `src/InvoiceCalculator.php:15`).
2. **Validation par ligne** : pour chaque ligne, `isset($ligne['quantite'], $ligne['prixUnitaire'])` est vérifié — si absent, `\InvalidArgumentException` levée immédiatement (`src/InvoiceCalculator.php:23-25`).
3. **Accumulation HT** : `$total += $ligne['quantite'] * $ligne['prixUnitaire']` (`src/InvoiceCalculator.php:26`).
4. **Arrondi et garde-fou d'entier** : `$rounded = round($total)` ; si `$rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN`, `\OverflowException` levée (`src/InvoiceCalculator.php:28-31`).
5. **Retour** : `(int) $rounded` (`src/InvoiceCalculator.php:32`).

### Chemin B — Calcul du total TTC (`totalTtc`)
1. **Constitution de la liste de lignes** : même structure, avec clé optionnelle `taux?: float` par ligne (docblock `src/InvoiceCalculator.php:36`).
2. **Validation par ligne** : même garde que HT — `\InvalidArgumentException` si `quantite` ou `prixUnitaire` absent (`src/InvoiceCalculator.php:45-47`).
3. **Accumulation TTC par ligne** : `$taux = $ligne['taux'] ?? self::TGC_STANDARD` puis `$total += $ligne['quantite'] * $ligne['prixUnitaire'] * (1 + $taux)` (`src/InvoiceCalculator.php:48-49`). Chaque ligne est taxée à son propre taux.
4. **Arrondi unique sur le cumul et garde-fou** : `$rounded = round($total)` ; `\OverflowException` si hors bornes (`src/InvoiceCalculator.php:51-54`). L'arrondi est appliqué **une seule fois** sur le total cumulé, pas ligne à ligne.
5. **Retour** : `(int) $rounded` (`src/InvoiceCalculator.php:55`).

**Note architecturale** : `totalTtc` effectue sa propre boucle indépendante et **n'appelle pas** `totalHorsTaxe` en interne (`VÉRIFIÉ_CODE`).

## Règles métier
- **Ligne = quantité × prix unitaire (HT)** : `$ligne['quantite'] * $ligne['prixUnitaire']` (`src/InvoiceCalculator.php:26`). Pas de ristourne ni de remise au niveau ligne.
- **Deux taux TGC prédéfinis par constantes** : standard 16 % (`TGC_STANDARD = 0.16`, `src/InvoiceCalculator.php:11`) et réduit 5 % (`TGC_REDUIT = 0.05`, `src/InvoiceCalculator.php:12`). Ces constantes servent de référence et de valeur par défaut ; la clé `taux` par ligne **n'est pas bornée par le code** — tout `float` est syntaxiquement accepté (`src/InvoiceCalculator.php:48`).
- **Taux par ligne** : chaque ligne peut fournir sa propre clé `taux` ; à défaut le taux standard s'applique (`$taux = $ligne['taux'] ?? self::TGC_STANDARD`, `src/InvoiceCalculator.php:48`). **Une même facture peut mélanger les taux** (standard, réduit, ou tout autre float passé en `taux`).
- **Présence obligatoire de `quantite` et `prixUnitaire`** : toute ligne ne disposant pas des deux clés lève `\InvalidArgumentException` (HT : `src/InvoiceCalculator.php:23-25` ; TTC : `src/InvoiceCalculator.php:45-47`).
- **Protection contre le débordement d'entier** : si le total arrondi dépasse `PHP_INT_MAX` ou est inférieur à `PHP_INT_MIN`, `\OverflowException` est levée (HT : `src/InvoiceCalculator.php:29-31` ; TTC : `src/InvoiceCalculator.php:52-54`).
- **Arrondi unique sur le cumul** : `round()` appliqué sur le total agrégé, pas sur chaque ligne individuelle (`src/InvoiceCalculator.php:28,51`). Sur une facture à taux mixte, cela peut produire un résultat différent d'un arrondi par ligne.
- **Montants en francs CFP entiers** : prix unitaires et quantités sont déclarés `int` dans les docblocks (`src/InvoiceCalculator.php:15,36`) — contrat d'entrée déclaré, aucune vérification de type ou de signe n'est effectuée sur les valeurs passées dans les lignes. Le retour est un `int` (`src/InvoiceCalculator.php:19,41`).

## Données
- **Ligne de facture HT** : tableau associatif `{label: string, quantite: int, prixUnitaire: int}` — transmis en paramètre, jamais persisté (`src/InvoiceCalculator.php:15`)
- **Ligne de facture TTC** : idem + clé optionnelle `taux?: float` (`src/InvoiceCalculator.php:36`)
- **TGC_STANDARD** : constante de classe `0.16` (`src/InvoiceCalculator.php:11`)
- **TGC_REDUIT** : constante de classe `0.05` (`src/InvoiceCalculator.php:12`)
- **total HT** : `int` retourné par `totalHorsTaxe` — résultat de calcul, non persisté
- **total TTC** : `int` retourné par `totalTtc` — résultat final consommé par l'appelant

## Intégrations
Aucune intégration externe explicite visible. Bibliothèque pure : pas d'appel réseau, pas de base de données, pas de fichier.

## Risques
- **Valeurs négatives non rejetées** : quantité ou prix unitaire négatifs ne sont pas validés — le calcul produirait un HT ou TTC négatif sans erreur. Comportement non testé ni documenté comme cas métier.
- **`taux` libre non borné** : la clé `taux` par ligne accepte n'importe quel `float` — un taux négatif ou supérieur à 1 est syntaxiquement valide et produira un résultat sans erreur. Aucun garde présent (`src/InvoiceCalculator.php:48`).
- **Tableau vide** : `totalHorsTaxe([])` et `totalTtc([])` retournent `0` sans erreur. Comportement cohérent mais non documenté comme cas métier attendu.
- **Arrondi unique vs arrondi par ligne** : sur une facture à taux mixte, l'arrondi appliqué une seule fois sur le cumul peut différer d'un arrondi effectué ligne par ligne. La règle est cohérente dans le code mais non documentée comme décision métier explicite.

## Questions ouvertes
- Le comportement sur liste vide (`[]`) est-il `0` ou devrait-il lever une erreur métier ?
- Les quantités et prix négatifs sont-ils des entrées licites (avoirs, remises) ou des cas à rejeter ?
- La clé `taux` par ligne accepte-t-elle tout float, ou est-elle censée n'accepter que `TGC_STANDARD` et `TGC_REDUIT` ? Un taux arbitraire est-il un usage prévu ?
- Le mode d'arrondi (`round()` défaut PHP) est-il délibérément aligné sur la réglementation CFP ou choisi par commodité ?
- La bibliothèque est-elle destinée à rester un calcul pur, ou intégrera-t-elle à terme une persistance ou une génération de document (PDF de facture) ?

## Preuves
- `src/InvoiceCalculator.php` (lu intégralement, 57 lignes)
- `tests/InvoiceCalculatorTest.php` (lu intégralement, 12 cas : `testTotalHorsTaxe` / `testTotalTtcTauxStandard` / `testTotalTtcTauxReduit` / `testTotalTtcTauxMixte` / `testTotalTtcSansTauxUtiliseTauxStandard` / 4× `Invalid­ArgumentException` / 2× `OverflowException`)
- `composer.json` (structure du projet, version PHP)

## Réconciliation (run SHIAAAAAAAAAAAAAAAAAAAAAAAA-498, 2026-08-08, SHA `6d4f877`)

La version précédente de ce fichier documentait le code d'avant CLA-250 (taux unique par facture via `bool $tauxReduit`). Dérives corrigées :

| Section | Dérive précédente | Correction |
|---|---|---|
| Points d'entrée | `totalTtc(array $lignes, bool $tauxReduit = false): int` | `totalTtc(array $lignes): int` — paramètre `$tauxReduit` retiré (CLA-250) |
| Étapes — Chemin B | « Choix du taux via `$tauxReduit` » ; « `(int) round($ht * (1 + $taux))` » ; « appelle `totalHorsTaxe` en interne » | Taux par ligne `$taux = $ligne['taux'] ?? TGC_STANDARD` ; boucle propre avec accumulation TTC par ligne ; `totalTtc` n'appelle pas `totalHorsTaxe` |
| Règles métier | « Taux unique par facture » ; « Pas de taux personnalisé » | Taux par ligne avec défaut `TGC_STANDARD` ; taux mixte possible et testé |
| Règles métier (absentes) | `InvalidArgumentException` et `OverflowException` non documentées | Ajout des deux règles avec preuves (`src/InvoiceCalculator.php:23-25,29-31,45-47,52-54`) |
| Risques | « Clés manquantes : comportement non documenté » | Ce n'est plus un risque : `InvalidArgumentException` lève explicitement. Retiré des risques |
| Risques | « Mélange de taux impossible » | Faux dans le code courant : le taux mixte est supporté par ligne et testé (`testTotalTtcTauxMixte`). Retiré |
| Données — Ligne TTC | `{label, quantite, prixUnitaire}` seulement | Ajout de `taux?: float` (docblock `src/InvoiceCalculator.php:36`) |
| Preuves — tests | 3 tests | 12 tests (ajout cas taux mixte, taux par défaut, 4× InvalidArgument, 2× Overflow) |
