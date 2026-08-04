# Relecture — CAHIER_RECETTE.md

## Verdict global
À corriger — le document est le plus problématique du lot. Il mélange cas de recette réellement dérivés de l'amont avec une longue série de scénarios inventés, de recommandations de correction et d'hypothèses runtime qui ne relèvent pas d'un cahier de recette prouvé par les workflows.

## Problèmes bloquants
- Le cahier de recette introduit de nombreux scénarios sans ancrage amont explicite : `quantité zéro`, `message long 100 KB`, `destination inaccessible`, `migration Monolog 1.x → 2.x`, `flux complet try/catch`, etc. Or le skill exige des parcours de recette dérivés d'un workflow réel ; les workflows amont ne portent pas ces scénarios comme parcours métier à tester. Sources consultées : `.onboarding/workflows/WORKFLOW_CALCUL_FACTURE_TGC.md`, `.onboarding/workflows/WORKFLOW_JOURNALISATION_FACTURATION.md`.
- Plusieurs "résultats attendus" décrivent un comportement d'exécution non observé du runtime PHP ou de Monolog (`Undefined array key`, écriture effective sur stderr/fichier, type d'exception au changement de version Monolog). Cela dépasse la preuve disponible dans le dépôt ; mêmes sources amont + `src/AppLogger.php`, `src/InvoiceCalculator.php`.
- Le document dérive vers un plan d'amélioration (`ajouter TestHandler`, `wrapper try/catch`, `adapter addInfo -> info`) plutôt qu'un cahier de recette centré sur des scénarios testables existants. Ces recommandations ne sont pas des cas de recette.

## Problèmes mineurs
- Certains scénarios "dérivés" sont acceptables mais devraient être explicitement séparés des scénarios prouvés par tests existants, pour éviter de noyer la matière solide.
- Le résumé de couverture emploie `Aucun` comme niveau de risque sur des cas non testés "dérivés logiquement" ; cette qualification mériterait plus de prudence.

## Points vérifiés et corrects
- Les scénarios nominaux HT, TTC standard, TTC réduit, ainsi que le taux par défaut, sont bien traçables aux tests `tests/InvoiceCalculatorTest.php` et au workflow calcul.
- La limitation multi-taux est bien identifiée à partir de `src/InvoiceCalculator.php:26-30`, déjà présente dans le workflow calcul.
- Le document marque souvent `HYPOTHÈSE`, ce qui montre une bonne intention de prudence ; le problème vient surtout du volume de scénarios non dérivés d'un workflow réel.

## Recommandations de correction
- Recentrer le cahier sur quelques parcours testables directement issus des workflows amont : calcul HT, TTC standard, TTC réduit, usage de `factureEmise`, usage de `erreurCalcul`, et éventuellement la limitation multi-taux comme non-support.
- Sortir du cahier de recette les propositions de design ou de remédiation (`try/catch`, `TestHandler`, migration Monolog 2.x`), qui relèvent plutôt des audits/recommandations techniques.
- Quand un scénario est conservé en hypothèse, expliciter la source amont qui justifie son existence ; sinon le retirer.
