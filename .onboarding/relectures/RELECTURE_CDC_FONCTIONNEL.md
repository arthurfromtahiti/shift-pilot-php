# Relecture — CDC_FONCTIONNEL.md

## Verdict global
À corriger — le CDC est riche, mais il dépasse régulièrement la preuve disponible et transforme des déductions techniques en règles fonctionnelles. La matière amont est utilisée, mais plusieurs formulations inventent soit de l'intention métier, soit des comportements d'exécution non observés.

## Problèmes bloquants
- Le document pose `label`, `quantite` et `prixUnitaire` comme `trois clés obligatoires` dans la règle métier "Ligne de facture". Or l'amont ne prouve qu'un docblock de structure (`src/InvoiceCalculator.php:15`) et le code ne consomme effectivement que `quantite` et `prixUnitaire` (`src/InvoiceCalculator.php:21`) ; présenter cela comme obligation métier est trop fort. Sources amont : `.onboarding/workflows/WORKFLOW_CALCUL_FACTURE_TGC.md`, `.onboarding/domaines/CARTE_DES_DOMAINES.md`.
- `Produits de première nécessité` est utilisé comme cas d'usage métier établi du taux réduit dans plusieurs sections. Cette association existe dans les workflows amont, mais elle est déjà une interprétation métier non prouvée par le code ; il ne faut pas la durcir dans le CDC comme règle certaine sans marquage d'hypothèse. Sources consultées : `.onboarding/workflows/WORKFLOW_CALCUL_FACTURE_TGC.md`, `src/InvoiceCalculator.php:11-12`.
- Le CDC introduit un `contournement possible` pour les factures multi-taux (`appeler totalTtc() deux fois et additionner`) comme solution concrète. L'amont constate la limitation ; il ne valide pas ce contournement comme comportement métier ou recette de référence. Source : `.onboarding/workflows/WORKFLOW_CALCUL_FACTURE_TGC.md` ne l'établit pas comme règle approuvée ; le code ne montre aucun orchestrateur.
- Les parcours de journalisation décrivent l'écriture effective de logs sur stderr/fichier comme résultat attendu certain, alors que le workflow amont borne explicitement cela à une attente dépendante du comportement interne de Monolog, non observé dans ce dépôt. Sources : `.onboarding/workflows/WORKFLOW_JOURNALISATION_FACTURATION.md`, `src/AppLogger.php:15-28`.

## Problèmes mineurs
- `Pas de ristourne ou de remise au niveau ligne` est déduit de l'absence de champ dédié. C'est acceptable comme hors-périmètre technique, mais trop affirmatif comme règle métier sans source amont explicite autre que l'absence de code.
- Les sections `Hors périmètre` listent beaucoup d'éléments plausibles, mais une partie relève de remplissage par absence (SIRET, multi-devise, archivage) plutôt que d'une synthèse directe des workflows/audits.
- `Monolog 1.x en fin de vie` et le détail de migration 2.x sont parfois exprimés comme risque confirmé, alors que la carte des domaines ne permet qu'une hypothèse externe.

## Points vérifiés et corrects
- L'objectif général de bibliothèque de calcul TGC sans HTTP ni persistance est correctement traçable à `.onboarding/domaines/CARTE_DES_DOMAINES.md`, `README.md` et `composer.json`.
- Les signatures `totalHorsTaxe` et `totalTtc`, le taux standard par défaut et l'arrondi via `round()` sont fidèlement repris du code (`src/InvoiceCalculator.php:17-30`).
- La limitation `un seul taux par facture` est bien tirée de la signature `bool $tauxReduit`, déjà pointée dans le workflow calcul.

## Recommandations de correction
- Revenir à des règles strictement prouvées par les workflows/audits/domaines ; dès qu'une intention métier n'est pas observée, la marquer comme `HYPOTHÈSE`.
- Réduire les listes "hors périmètre" aux absences explicitement utiles et traçables, plutôt que d'énumérer des fonctionnalités génériques non présentes.
- Sur la journalisation, distinguer nettement `ce que fait AppLogger` de `ce que Monolog écrira effectivement`, qui n'est pas prouvé ici.
