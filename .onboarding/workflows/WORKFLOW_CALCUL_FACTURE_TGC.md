# WORKFLOW_CALCUL_FACTURE_TGC — Calcul HT et TTC d'une facture avec TGC

## Classification
- **Type** : business_process
- **Sous-type** : calcul monétaire avec taxe sectorielle
- **Visibilité** : technical
- **Acteur principal** : application consommatrice de la bibliothèque
- **Acteurs** : code appelant PHP, `App\InvoiceCalculator`
- **Criticité** : Haute — seule fonction métier du dépôt ; sans ce calcul, la bibliothèque n'a pas de raison d'être
- **Confiance** : high (chemin nominal) — les cas limites (clés manquantes, valeurs négatives) restent des hypothèses non testées
- **Justification** : les deux méthodes publiques (`totalHorsTaxe`, `totalTtc`) ont été lues intégralement (`src/InvoiceCalculator.php`) ; trois tests PHPUnit couvrent les cas nominaux (HT, TTC standard, TTC réduit) et sont concordants avec le code (`tests/InvoiceCalculatorTest.php`). Aucun test de cas limite n'est disponible.

## Objectif
Permettre à une application PHP de calculer le **total hors taxe** puis le **total toutes taxes comprises (TTC)** d'une facture composée de plusieurs lignes, en appliquant la **TGC** (taxe générale sur la consommation, Polynésie française) au taux standard (16 %) ou au taux réduit (5 %, produits de première nécessité). Les montants sont en **francs CFP entiers**. La bibliothèque ne persiste rien, ne génère aucun document : elle calcule et retourne.

## Acteurs
- **Application consommatrice** : code PHP tiers qui instancie `InvoiceCalculator` et lui fournit les lignes de facture
- **`App\InvoiceCalculator`** (`src/InvoiceCalculator.php`) : agrège les lignes et applique le taux TGC

## Points d'entrée
- `App\InvoiceCalculator::totalHorsTaxe(array $lignes): int` — calcul du total HT seul
- `App\InvoiceCalculator::totalTtc(array $lignes, bool $tauxReduit = false): int` — calcul du total TTC (appelle `totalHorsTaxe` en interne)

## Étapes principales
1. **Constitution de la liste de lignes** : l'appelant bâtit un tableau `$lignes` ; chaque entrée a la forme `{label: string, quantite: int, prixUnitaire: int}` (docblock `src/InvoiceCalculator.php:15`).
2. **Calcul du total HT** : `totalHorsTaxe` itère les lignes et accumule `$ligne['quantite'] * $ligne['prixUnitaire']` (`src/InvoiceCalculator.php:19-22`). Retourne un `int`.
3. **Choix du taux TGC** : `totalTtc` sélectionne `TGC_REDUIT = 0.05` si `$tauxReduit === true`, sinon `TGC_STANDARD = 0.16` (`src/InvoiceCalculator.php:29`).
4. **Calcul du total TTC et arrondi** : `(int) round($ht * (1 + $taux))` (`src/InvoiceCalculator.php:30`). Résultat : `int` en francs CFP.

## Règles métier
- **Ligne = quantité × prix unitaire** : `$ligne['quantite'] * $ligne['prixUnitaire']` (`src/InvoiceCalculator.php:21`). Pas de ristourne ni de remise au niveau ligne.
- **Deux taux de TGC seulement** : standard 16 % (`TGC_STANDARD = 0.16`, `src/InvoiceCalculator.php:11`) et réduit 5 % (`TGC_REDUIT = 0.05`, `src/InvoiceCalculator.php:12`). Pas de taux zéro, pas de taux personnalisé.
- **Taux unique par facture** : le paramètre `$tauxReduit` s'applique à l'ensemble du total HT ; une facture ne peut pas panacher taux standard et taux réduit sur des lignes différentes.
- **Taux standard par défaut** : `bool $tauxReduit = false` (`src/InvoiceCalculator.php:27`) — si non précisé, le taux standard s'applique.
- **Arrondi au franc CFP** : `(int) round(...)` (`src/InvoiceCalculator.php:30`). `round()` est appelé sans mode d'arrondi explicite (aucun second argument dans le code).
- **Pas de sous-unité** : prix unitaires et quantités sont des entiers (`int`), confirmé par les cas de test (10000 F CFP, 5000 F CFP, quantités 1 et 2).

## Données
- **Ligne de facture** : tableau associatif `{label: string, quantite: int, prixUnitaire: int}` — transmis en paramètre, jamais persisté (`src/InvoiceCalculator.php:15`)
- **TGC_STANDARD** : constante de classe `0.16` (`src/InvoiceCalculator.php:11`)
- **TGC_REDUIT** : constante de classe `0.05` (`src/InvoiceCalculator.php:12`)
- **total HT** : `int` retourné par `totalHorsTaxe` — intermédiaire de calcul
- **total TTC** : `int` retourné par `totalTtc` — résultat final consommé par l'appelant

## Intégrations
Aucune intégration externe explicite visible. Bibliothèque pure : pas d'appel réseau, pas de base de données, pas de fichier.

## Risques
- **Clés manquantes dans une ligne** : accès directs `$ligne['quantite']` et `$ligne['prixUnitaire']` sans garde ni validation dans la boucle (`src/InvoiceCalculator.php:21`). Aucun test ne couvre ce cas (`tests/InvoiceCalculatorTest.php`). `HYPOTHÈSE` : le comportement runtime en cas de clé absente (avertissement, résultat inattendu, autre) n'est pas documenté dans le dépôt et n'a pas pu être observé.
- **Valeurs négatives** : quantité ou prix unitaire négatifs ne sont pas rejetés — le calcul produirait un HT négatif sans erreur.
- **Tableau vide** : `totalHorsTaxe([])` retourne `0`, `totalTtc([])` retourne `0`. Comportement cohérent mais non documenté comme cas métier attendu.
- **Mélange de taux impossible** : une même facture ne peut pas combiner lignes à taux standard et lignes à taux réduit — limitation architecturale visible dans la signature (`bool $tauxReduit`).

## Questions ouvertes
- Le comportement sur liste vide (`[]`) est-il `0` ou devrait-il lever une erreur métier ?
- Les quantités et prix négatifs sont-ils des entrées licites (avoirs, remises) ou des cas à rejeter ?
- Le mode d'arrondi (`round()` défaut PHP) est-il délibérément aligné sur la réglementation CFP ou choisi par commodité ?
- La bibliothèque est-elle destinée à rester un calcul pur, ou intégrera-t-elle à terme une persistance ou une génération de document (PDF de facture) ?

## Preuves
- `src/InvoiceCalculator.php` (lu intégralement)
- `tests/InvoiceCalculatorTest.php` (lu intégralement — 3 tests : `testTotalHorsTaxe`, `testTotalTtcTauxStandard`, `testTotalTtcTauxReduit`)
