# Fonctionnel — Audit

> Confiance : high sur le périmètre visible (code source + tests) — medium sur les intentions métier (pas d'accès aux spécifications fonctionnelles au-delà du README et des docblocks). Audit de réconciliation confronté au code courant (2026-08-08).

## Compréhension globale

`shift-pilot-php` implémente un sous-ensemble restreint de la facturation TGC : calcul HT/TTC avec taux TGC par ligne, journalisation des événements. La limitation la plus significative de la première version — l'impossibilité de panacher des taux TGC différents sur la même facture — est désormais levée. Il n'y a toujours ni génération de document PDF, ni persistance, ni notion de client ou de fournisseur, ni gestion de remises ou d'avoirs.

## Résumé exécutif

Le périmètre fonctionnel livré est **cohérent avec lui-même** et a évolué positivement. La principale limitation identifiée lors du premier audit est résolue : `totalTtc` supporte désormais un champ optionnel `taux?: float` par ligne, avec repli sur `TGC_STANDARD` (16 %) si la clé est absente (`src/InvoiceCalculator.php:48`). Les factures à taux mixtes (lignes à 16 % et lignes à 5 %) sont maintenant représentables et testées. Les gardes d'entrée manquantes ont également été ajoutées : `\InvalidArgumentException` sur clé manquante, `\OverflowException` sur dépassement. Les limites persistantes sont : l'absence de documentation d'un exemple d'utilisation combinée des deux classes, le comportement du taux par défaut silencieux (un consommateur qui omet `taux` obtient 16 % sans avertissement), et le mode d'arrondi non documenté vis-à-vis de la réglementation CFP.

## Constats détaillés

**Cohérence calcul HT/TTC.** `VÉRIFIÉ_CODE` — `totalHorsTaxe` retourne la somme des `quantite × prixUnitaire` pour chaque ligne, protégée par `round()` + vérification overflow + `(int)` (`src/InvoiceCalculator.php:21-33`) ; `totalTtc` applique le même processus en intégrant le taux par ligne : `$ligne['quantite'] * $ligne['prixUnitaire'] * (1 + $taux)` (`src/InvoiceCalculator.php:49`). Les cinq premiers tests valident les calculs nominaux et les calculs à taux mixtes — résultats arithmétiquement corrects. Aucune incohérence entre l'implémentation et son intention déclarée.

**Taux TGC par ligne — limitation levée.** `VÉRIFIÉ_CODE` — la signature est désormais `totalTtc(array $lignes): int` (`src/InvoiceCalculator.php:41`). Chaque ligne peut porter une clé `taux?: float` ; sans cette clé, `self::TGC_STANDARD` s'applique par défaut (`src/InvoiceCalculator.php:48`). Il est maintenant possible de calculer le TTC d'une facture dont certaines lignes sont à 16 % et d'autres à 5 %. Ce cas est testé (`tests/InvoiceCalculatorTest.php:34-45` : `testTotalTtcTauxMixte`).

**Taux par défaut silencieux.** `VÉRIFIÉ_CODE` — `$taux = $ligne['taux'] ?? self::TGC_STANDARD` (`src/InvoiceCalculator.php:48`) applique `TGC_STANDARD` (16 %) sans signal si la clé `taux` est absente. Ce comportement est couvert par `testTotalTtcSansTauxUtiliseTauxStandard` (`tests/InvoiceCalculatorTest.php:47-52`), mais n'est pas documenté dans le README. `HYPOTHÈSE` : un consommateur qui omet la clé `taux` pour une ligne devant être taxée à 5 % obtiendra 16 % sans avertissement — risque de facturation incorrecte non détectée.

**Taux TGC codés en constantes.** `VÉRIFIÉ_CODE` — le dépôt encode `TGC_STANDARD = 0.16` (16 %) et `TGC_REDUIT = 0.05` (5 %) (`src/InvoiceCalculator.php:11-12`). `HYPOTHÈSE` (contexte externe, non sourcé dans le dépôt) : ces valeurs sembleraient correspondre aux taux de la TGC polynésienne — le rattachement réglementaire exact (décret, date d'entrée en vigueur) ne peut pas être établi depuis le seul code.

**Gardes d'entrée fonctionnelles.** `VÉRIFIÉ_CODE` — les deux méthodes lèvent `\InvalidArgumentException` si `quantite` ou `prixUnitaire` est absent (`src/InvoiceCalculator.php:23-25`, `45-47`), et `\OverflowException` si le total dépasse `PHP_INT_MAX` (`src/InvoiceCalculator.php:29-31`, `52-54`). Ces comportements sont documentés dans les docblocks et testés (`tests/InvoiceCalculatorTest.php:54-101`).

**Pas d'intégration visible entre les deux classes.** `VÉRIFIÉ_CODE` — aucun fichier dans le dépôt (`src/`, `tests/`, racine) ne montre un exemple d'appel combinant `InvoiceCalculator::totalTtc` et `AppLogger::factureEmise`. Les noms de méthodes (`factureEmise`, `erreurCalcul`) évoquent un usage séquentiel, mais aucun code ni documentation n'illustre ce flux dans le dépôt. `HYPOTHÈSE` : l'intégration est prévue côté application hôte — mais en l'absence d'exemple, un développeur découvrant la bibliothèque n'a pas de modèle d'utilisation.

**Mode d'arrondi : comportement par défaut non documenté.** `VÉRIFIÉ_CODE` — `round()` est appelé sans second argument dans `src/InvoiceCalculator.php:28` et `src/InvoiceCalculator.php:51`. `HYPOTHÈSE` (contexte langage) : PHP appliquerait `PHP_ROUND_HALF_UP` par défaut — ce n'est pas une preuve issue du dépôt. La règle d'arrondi applicable à la TGC polynésienne n'est pas documentée dans le dépôt. `HYPOTHÈSE` : si la règle fiscale requiert un mode différent pour certaines valeurs limites, le résultat serait fonctionnellement incorrect sur ces cas.

**Comportement sur tableau vide.** `VÉRIFIÉ_CODE` — `totalHorsTaxe([])` retourne `0` (la boucle ne s'exécute pas, `round(0) = 0`, `(int)0 = 0`) ; `totalTtc([])` retourne `0` (idem). Ce comportement n'est ni testé ni documenté comme cas métier attendu. `HYPOTHÈSE` : une facture vide devrait peut-être lever une erreur fonctionnelle plutôt que retourner 0 F CFP, selon les règles métier.

## Forces

- `VÉRIFIÉ_CODE` : limitation taux unique par facture résolue — `src/InvoiceCalculator.php:48` supporte `taux?: float` par ligne, testé dans `tests/InvoiceCalculatorTest.php:34-45`.
- `VÉRIFIÉ_CODE` : cohérence entre les signatures, les docblocks et l'implémentation sur les chemins lus — les structures déclarées correspondent aux implémentations observées dans le code source. `HYPOTHÈSE` : l'absence de divergence à l'exécution est déduite de cette lecture statique ; les tests n'ont pas été exécutés dans cet audit et le comportement runtime n'a pas été observé.
- `VÉRIFIÉ_CODE` : gardes d'entrée explicites et documentées pour les clés manquantes (`\InvalidArgumentException`) et l'overflow (`\OverflowException`).
- `VÉRIFIÉ_CODE` : `InvoiceCalculator` est sans effet de bord — pas de persistance, pas d'écriture de fichier, pas d'appel réseau — facilement testable et prévisible. `AppLogger` peut écrire vers la destination configurée via son `StreamHandler` Monolog (`src/AppLogger.php:15-18`) ; son effet de bord est circonscrit à cette destination et ne concerne pas `InvoiceCalculator`.

## Dettes techniques

- `VÉRIFIÉ_CODE` : taux par défaut silencieux — un consommateur qui omet la clé `taux` obtient `TGC_STANDARD` (16 %) sans signal, comportement non documenté dans le README (`src/InvoiceCalculator.php:48`).
- `HYPOTHÈSE` : absence d'exemple d'utilisation combinée — aucun code n'illustre le workflow complet (calcul + log) dans le dépôt.
- `HYPOTHÈSE` : mode d'arrondi non documenté — conformité avec la règle fiscale CFP non prouvée pour les cas limites.
- `VÉRIFIÉ_CODE` : comportement sur tableau vide non documenté comme cas métier (`src/InvoiceCalculator.php:21-33`).

## Zones critiques

- **`src/InvoiceCalculator.php:48`** — le mécanisme de taux par ligne avec repli sur `TGC_STANDARD` est désormais le seul point de contrôle du taux appliqué. Un consommateur qui n'inclut pas de clé `taux` obtiendra toujours 16 %, sans signal explicite. C'est correct selon le contrat docblock, mais non documenté dans le README.
- **Absence de code d'intégration** — un développeur qui reprend la bibliothèque pour l'intégrer dans une application n'a pas de point de départ visible dans le dépôt.

## Risques

- `VÉRIFIÉ_CODE` + `HYPOTHÈSE` : **taux par défaut silencieux** — si un consommateur omet la clé `taux` dans une ligne qui devrait utiliser le taux réduit, `TGC_STANDARD` (16 %) est appliqué sans avertissement. Impact : facturation à 16 % au lieu de 5 % pour des produits de première nécessité, sans erreur, sans signal. Preuve : `src/InvoiceCalculator.php:48`. Non documenté dans le README.
- `HYPOTHÈSE` : **mode d'arrondi non conforme** — si la réglementation CFP impose un arrondi différent de `PHP_ROUND_HALF_UP` pour certaines valeurs, la bibliothèque produit des montants incorrects sur ces cas. Risque faible pour un pilote ; réel si la bibliothèque sert à des factures légalement opposables.

## Recommandations priorisées

1. **Documenter le comportement du taux par défaut dans le README** — expliquer explicitement que sans clé `taux`, chaque ligne est taxée à `TGC_STANDARD` (16 %). Un consommateur qui omettrait la clé pour une ligne à 5 % obtiendrait 16 % sans signal d'erreur. Fichier : `README.md`.
2. **Ajouter un exemple d'utilisation** dans le README ou un fichier dédié (`examples/`) montrant le flux complet : construction de lignes avec et sans `taux`, appel `totalTtc`, appel `factureEmise`. Fichier : `README.md` ou `examples/usage.php`.
3. **Documenter le mode d'arrondi** — référencer la règle réglementaire CFP applicable, ou noter que `PHP_ROUND_HALF_UP` est utilisé par défaut faute de spécification explicite. Fichier : `src/InvoiceCalculator.php:28,51`.
4. **Clarifier le comportement sur tableau vide** — soit documenter comme cas licite (0 F CFP retourné), soit lever une `\InvalidArgumentException`. Fichier : `src/InvoiceCalculator.php`, `README.md`.

## Questions ouvertes

- Le mode d'arrondi `PHP_ROUND_HALF_UP` est-il conforme à la réglementation fiscale TGC polynésienne pour les montants en francs CFP ?
- La bibliothèque est-elle destinée à évoluer vers une génération de document de facture (PDF, JSON structuré), ou restera-t-elle un composant de calcul pur ?
- Qui sont les consommateurs prévus (application métier interne, API exposée, autre bibliothèque) ? La réponse détermine le niveau de documentation et de validation d'entrée nécessaire.
- Le comportement « taux par défaut = TGC_STANDARD » est-il documenté quelque part en dehors du code pour les consommateurs de la bibliothèque ?
