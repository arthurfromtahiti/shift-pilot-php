# Corrections — Étape 4, Rédaction des documents

**Date** : 2026-08-09  
**Exécuteur** : Agent Rédacteur (ba2dd109…)  
**Verdict antécédent** : RELECTURE_DOCUMENTS_ETAPE4.md (run 6095cba2…) — À CORRIGER  
**Commit** : `afa7534`

---

## Corrections apportées

### 1. CDC_FONCTIONNEL.md — Élimination des garanties excessives d'exécution

**Problème** : les parcours utilisateur (lignes 75, 88, 101, 115, 128) disaient que les tests « retournent » leurs résultats, impliquant une exécution observée, alors que le cahier de recette prouve seulement la présence et la cohérence arithmétique des assertions.

**Corrections** :
- Ligne 75 (Cas 1 : Calcul HT) : « assertion attendue `25000` (exécution À OBSERVER) » → « assertion arithmétiquement correcte `25000` ; exécution À OBSERVER »
- Ligne 88 (Cas 2 : TTC standard) : même pattern
- Ligne 101 (Cas 3 : TTC réduit) : même pattern
- Ligne 115 (Cas 4 : TTC mixte) : même pattern
- Ligne 128 (Cas 5 : Taux par défaut) : même pattern
- Ligne 178 (R6 — Taux par ligne) : « assertion attendue `22100` » → « assertion arithmétiquement correcte `22100` »

**Impact** : distinction nette entre preuve statique (assertion présente et arithmétiquement juste) et exécution runtime (non observée à ce stade).

### 2. CDC_FONCTIONNEL.md — Qualification des critères d'acceptation

**Corrections** :
- **Ligne 306 (critère 3, validation d'entrée)** : ajout des références de code précises (`src/InvoiceCalculator.php:23-25,45-47` pour InvalidArgumentException ; `src/InvoiceCalculator.php:29-31,52-54` pour OverflowException) et qualification explicite « test présent ; exécution À OBSERVER »
- **Ligne 308 (critère 5, taux mixte)** : remplacé « preuve statique présente » par « test `testTotalTtcTauxMixte` avec assertion arithmétiquement correcte `22100` »
- **Lignes 309-310 (critères 6-7, journalisation)** : qualification précise du code Monolog (`src/AppLogger.php:23,28` pour l'observation statique) et distinction entre transmission du message et configuration Monolog externe

### 3. README.md — Résolution des incohérences déclaratives

**Problème** : README.md affirmait PHP 8.0 et composer.lock non versionné, alors que composer.json et la réalité du dépôt indiquaient PHP 8.1+ et lock versionné.

**Corrections** :
- Ligne 7 : « PHP >= 8.0, Composer » → « PHP >= 8.1, Composer »
- Ligne 13 : « Le fichier `composer.lock` n'est pas versionné à ce jour. » → « Le fichier `composer.lock` est versionné pour garantir la reproductibilité des dépendances. »
- Ligne 14 : « La journalisation repose sur l'API Monolog actuellement en place. » → « La journalisation repose sur l'API Monolog 3.x (méthodes `info()` et `error()`) »

**Impact** : README.md en cohérence stricte avec composer.json et le code source.

### 4. CAHIER_RECETTE.md — Statuts de recette sans ambiguïté

**Problème** : les cases de recette (Blocs A et B) utilisaient « ✓ À_OBSERVER » comme statut, ce qui était ambigu : était-ce un succès constaté (✓) ou une exécution non confirmée (À_OBSERVER) ?

**Corrections** :
- Ligne 7 (note d'introduction) : « le cahier distingue `VÉRIFIÉ_CODE` (source lu et validé statiquement) de `OBSERVÉ` (exécution runtime confirmée) » → remplacé `OBSERVÉ` par `ATTENTE` (exécution runtime à confirmer)
- Lignes 392-397 (Bloc A — Tests nominaux) : remplacement des 5 cases « ✓ À_OBSERVER » par « [ ] ATTENTE (assertions statiquement correctes, exécution runtime à confirmer) »
- Lignes 405-412 (Bloc B — Tests d'exception) : remplacement des 7 cases « ✓ À_OBSERVER » par « [ ] ATTENTE (exception levée attendue, exécution runtime à confirmer) »
- Ligne 431 (résumé global) : mis à jour pour cohérence avec le nouveau vocabulaire (ATTENTE au lieu de À_OBSERVER)

**Impact** : distinction cristalline entre preuves statiques (VÉRIFIÉ_CODE : code lu, assertions validées) et exécution confirmée en runtime (cas d'usage futur).

---

## Vérifications effectuées

✅ Toutes les corrections sont à effet documentaire uniquement (aucune modification du code source ni des tests)  
✅ Harmonisation complète CDC_FONCTIONNEL.md / CAHIER_RECETTE.md / README.md  
✅ Distinction statique/runtime maintenue cohérente dans tout le corpus  
✅ Formulations « assertion attendue » éliminées au profit de « assertion arithmétiquement correcte ; exécution À OBSERVER »  
✅ Pas de nouvelle garantie sur l'exécution  

---

## Statut

Les documents sont désormais **en strict conformité** avec le verdict :
- ✅ Pas d'affirmation d'exécution sans preuve runtime
- ✅ Distinction claire statique/À OBSERVER partout
- ✅ Incohérences README.md résolues
- ✅ Statuts de recette sans ambiguïté

**Prêt pour relecture d'approbation.**
