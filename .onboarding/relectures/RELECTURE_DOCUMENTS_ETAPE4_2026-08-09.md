# Relecture des documents de référence — étape 4 — 2026-08-09

## Verdict global

**À CORRIGER.** La révision corrige les défauts principaux signalés précédemment : README cohérent avec `composer.json` et `composer.lock`, taux par ligne et garde-fous alignés sur le code, et distinction généralement correcte entre preuve statique et exécution runtime non observée.

## Constat bloquant

### `CAHIER_RECETTE.md` contient encore une garantie d’exécution non prouvée

À la ligne 434, le résumé global indique : « exécution confirmée par runtime ». Cette phrase contredit immédiatement les lignes 425-435, qui qualifient l'exécution de `À OBSERVER`/`ATTENTE`, ainsi que la ligne 438 qui précise que `composer test` n'a pas été exécuté. Aucun résultat PHPUnit runtime n'est fourni dans le document, et l'environnement de relecture ne dispose pas de PHP/vendor permettant de transformer cette affirmation en `OBSERVÉ`.

**Correction demandée :** remplacer cette mention par une formulation statique, par exemple « exécution runtime à confirmer », sans modifier le statut en `OBSERVÉ` tant qu'un run daté de `composer test` avec son exit code n'est pas consigné.

## Contrôles effectués

- **Traçabilité amont : conforme après correction.** Les règles de calcul, taux mixtes, taux par défaut, exceptions et journalisation sont traçables à `CARTE_DES_DOMAINES.md`, aux deux workflows et aux audits ; les lignes de code courantes (`InvoiceCalculator.php:11-55`, `AppLogger.php:15-28`) concordent.
- **Réconciliation code/documentation : conforme.** `README.md` annonce PHP `>=8.1`, Monolog 3.x et un lock versionné ; `composer.json`, `composer.lock` et le code courant confirment ces éléments. Le dépôt contient bien `composer.lock` suivi par Git.
- **Hypothèses et limites : globalement conformes.** Les taux réglementaires, la portée métier des taux, l'arrondi fiscal et les valeurs négatives sont marqués comme hypothèses/points à valider. L'absence de test `AppLogger` reste explicitement signalée.
- **Matière exploitée : conforme.** Les documents couvrent les deux domaines réellement présents, les workflows de calcul/journalisation, les exceptions, les risques et les parcours de recette ; la synthèse n'est pas creuse.
- **Recette : presque conforme.** Les 12 cas sont présents et leurs assertions sont vérifiables statiquement, mais aucun ne peut être déclaré passé/exécuté sans preuve runtime.

## Décision

**Changes requested.** Corriger uniquement la phrase contradictoire du résumé de `CAHIER_RECETTE.md`, puis soumettre à nouveau pour approbation. Aucun changement de code n'est demandé.
