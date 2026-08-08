# Index des artefacts d'onboarding — shift-pilot-php

Cet index recense **tous les fichiers** publiés dans `.onboarding/` de ce workspace, produits par l'onboarding shift-pilot-php. Il constitue le point d'entrée unique pour explorer la connaissance reconstituée du dépôt.

## Légende des colonnes

- **Type** : nature de l'artefact (domaine, workflow, audit, document, journal de fabrication)
- **Domaine(s)** : le(s) domaine(s) fonctionnel(s) concerné(s)
- **Workflow** : clé du workflow si applicable
- **Dépôt** : nom du workspace d'origine (ce dépôt : `shift-pilot-php`)
- **Fichier** : chemin relatif depuis `.onboarding/`
- **Date** : date de la dernière révision validée (postérieure au verdict du relecteur)
- **Version SHA** : commit qui a publié cette version exacte (déterminé par `git log -1 --format=%H -- <fichier>`)
- **Niveau de preuve** : `établi` si toutes les affirmations sont OBSERVÉ/VÉRIFIÉ_CODE ; `contient une hypothèse` sinon
- **Titre** : synthèse du contenu en une phrase (usage : classification sémantique, pas reformulation du nom)

---

## Artefacts d'analyse (étapes 1-3)

### Domaines

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| domaine | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `domaines/CARTE_DES_DOMAINES.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Deux domaines identifiés : calcul TGC et journalisation Monolog 1.x ; questions ouvertes sur hypothèse Monolog 2.0 |

### Workflows

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| workflow | facturation-tgc | CALCUL_FACTURE_TGC | shift-pilot-php | `workflows/WORKFLOW_CALCUL_FACTURE_TGC.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Calcul HT/TTC avec taux TGC unique ; limitation taux mixte ; cas limites non testés |
| workflow | journalisation-applicative | JOURNALISATION_FACTURATION | shift-pilot-php | `workflows/WORKFLOW_JOURNALISATION_FACTURATION.md` | 2026-08-04 | 5f5c8ee | établi | Journalisation des événements (facture émise, erreur de calcul) via Monolog 1.x |

### Audits

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| audit | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `audits/ARCHITECTURE_AUDIT.md` | 2026-08-08 | 7ef6351 | contient une hypothèse | Architecture minimale, migration Monolog 3.x réalisée, taux par ligne, incohérence version PHP dans README, pas de `composer.lock` |
| audit | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `audits/DATA_MODEL_AUDIT.md` | 2026-08-08 | 7ef6351 | contient une hypothèse | Pas de persistance, ligne de facture avec `taux?: float` par ligne, gardes isset ajoutées, taux non validé en plage |
| audit | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `audits/FUNCTIONAL_AUDIT.md` | 2026-08-08 | 7ef6351 | contient une hypothèse | Taux mixtes maintenant supportés, limitation taux unique résolue, taux par défaut silencieux, mode d'arrondi non documenté |
| audit | — | — | shift-pilot-php | `audits/CODE_HOTSPOTS_AUDIT.md` | 2026-08-08 | 7ef6351 | contient une hypothèse | Migration Monolog 3.x réalisée, gardes ajoutées, taux par ligne, taux non validé en plage, AppLogger sans test |
| audit | — | — | shift-pilot-php | `audits/SECURITY_ROBUSTNESS_AUDIT.md` | 2026-08-08 | 7ef6351 | contient une hypothèse | Aucune injection, gardes isset + InvalidArgumentException ajoutées, OverflowException ajoutée, valeurs négatives non rejetées |
| audit | — | — | shift-pilot-php | `audits/TESTING_AUDIT.md` | 2026-08-08 | 7ef6351 | contient une hypothèse | 12 tests (nominaux, taux mixtes, exceptions), `AppLogger` non testé, pas de couverture configurée, pas de `composer.lock` |

---

## Artefacts de référence (étape 4)

### Documents de référence

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| document | — | — | shift-pilot-php | `documents/PROJECT_CONTEXT.md` | 2026-08-08 | 192d047 | contient une hypothèse | ✅ Mise à jour — contexte métier, structure du dépôt (Monolog 3.x, composer.lock), domaines, fragilités (incohérences PHP/docs détectées), taux mixte levé |
| document | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `documents/CDC_FONCTIONNEL.md` | 2026-08-08 | 192d047 | contient une hypothèse | ✅ Mise à jour — cahier des charges : API taux par ligne (taux mixte supporté), gardes InvalidArgumentException/OverflowException, Monolog 3.x, 12 règles métier, données |
| document | — | — | shift-pilot-php | `documents/CARTOGRAPHIE_CODE.md` | 2026-08-08 | 192d047 | contient une hypothèse | ✅ Mise à jour — cartographie : 57 lignes InvoiceCalculator (gardes + taux par ligne), AppLogger 30 lignes (Monolog 3.x), 12 tests, zones critiques |
| document | — | — | shift-pilot-php | `documents/CAHIER_RECETTE.md` | 2026-08-08 | 192d047 | contient une hypothèse | ✅ Mise à jour — plan de test : 12 cas PHPUnit (5 nominaux + 7 exceptions), composer.lock 3.10.0, PHP 8.1 requis, AppLogger non testé |

---

## Journal de fabrication (traçabilité)

### Relectures des artefacts d'analyse

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_CARTE_DES_DOMAINES.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Carte des domaines : verdict, corrections, vérifications |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_WORKFLOW_CALCUL_FACTURE_TGC.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Workflow Calcul facture TGC |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_WORKFLOW_JOURNALISATION_FACTURATION.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Workflow Journalisation facturation |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_ARCHITECTURE_AUDIT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Audit Architecture |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_DATA_MODEL_AUDIT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Audit Data Model |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_FUNCTIONAL_AUDIT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Audit Fonctionnel |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_CODE_HOTSPOTS_AUDIT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Audit Code Hotspots |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_SECURITY_ROBUSTNESS_AUDIT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Audit Sécurité et Robustesse |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_TESTING_AUDIT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Audit Tests |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_AUDITS.md` | 2026-08-04 | 5f5c8ee | — | Synthèse des verdicts d'audit (6 audits relus) |

### Relectures des documents de référence

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_PROJECT_CONTEXT.md` | 2026-08-04 | 5f5c8ee | — | Relecture — PROJECT_CONTEXT |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_CDC_FONCTIONNEL.md` | 2026-08-04 | 5f5c8ee | — | Relecture — CDC Fonctionnel |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_CARTOGRAPHIE_CODE.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Cartographie du code |
| journal-fabrication | — | — | shift-pilot-php | `relectures/RELECTURE_CAHIER_RECETTE.md` | 2026-08-04 | 5f5c8ee | — | Relecture — Cahier de recette |

---

## Résumé par type

### Par étape de l'onboarding

| Étape | Artefacts | Statut | Point d'entrée |
|---|---|---|---|
| **Étape 1 — Découverte de domaines** | Carte des domaines + relecture | ✅ Validé | `domaines/CARTE_DES_DOMAINES.md` |
| **Étape 2 — Analyse de workflows** | 2 workflows + relectures | ✅ Validé | `workflows/` (2 fichiers) |
| **Étape 3 — Audits** | 6 audits + relectures | ✅ Validé | `audits/` (6 fichiers) |
| **Étape 4 — Documents de référence** | 4 documents + relectures | ✅ Validé | `documents/` (4 fichiers) |

### Par domaine métier

| Domaine | Artefacts | Statut |
|---|---|---|
| **Facturation TGC** | Carte, workflow calcul, audits fonctionnel/architecture, CDC, cartographie code, recette | ✅ Couvert |
| **Journalisation applicative** | Carte, workflow journalisation, audits architecture/sécurité, CDC, cartographie code, recette | ✅ Couvert |

### Par type d'artefact

| Type | Quantité | Localisation |
|---|---|---|
| Domaines | 1 | `domaines/` |
| Workflows | 2 | `workflows/` |
| Audits | 6 | `audits/` |
| Documents de référence | 4 | `documents/` |
| Journal de fabrication (relectures) | 14 | `relectures/` |
| **Total** | **27** | — |

---

## Niveau de confiance global du workspace

| Dimension | Confiance | Réserves |
|---|---|---|
| **Code source lu** | high | Intégralité de `src/` vérifiée (57 et 30 lignes) ; réconciliation au SHA `192d047` (2026-08-08) |
| **Règles métier identifiées** | high | 12 règles documentées, préuvées ou questionnées ; taux par ligne implémenté et testé |
| **Architecture comprise** | high | Deux classes, zéro dépendance croisée, Monolog 3.x, taux par ligne, gardes (InvalidArgumentException, OverflowException) |
| **Couverture de test** | medium | 12 tests sur `InvoiceCalculator` (nominaux, mixtes, exceptions) ; `AppLogger` non testé, pas de couverture configurée |
| **Conformité réglementaire** | low | Taux TGC supposés polynésiens, non sourcés ; mode d'arrondi non documenté |
| **Reproductibilité des builds** | high | ✅ `composer.lock` présent et versionné (Monolog 3.10.0, PHPUnit 10.5) depuis 2026-08-08 |

---

## Comment utiliser cet index

### Je dois comprendre le contexte du projet
→ Lire **`PROJECT_CONTEXT.md`** en premier

### Je dois implémenter une fonctionnalité
→ Lire **`CDC_FONCTIONNEL.md`** (règles métier), puis **`CARTOGRAPHIE_CODE.md`** (navigation du code)

### Je dois faire évoluer le code
→ Lire **`CARTOGRAPHIE_CODE.md`** (points critiques), puis les **`*_AUDIT.md`** correspondant à la zone modifiée

### Je dois tester la bibliothèque
→ Lire **`CAHIER_RECETTE.md`** (plan de test complet)

### Je dois revoir l'architecture
→ Lire **`ARCHITECTURE_AUDIT.md`** et **`PROJECT_CONTEXT.md`** (fragilités)

### Je dois vérifier la sécurité
→ Lire **`SECURITY_ROBUSTNESS_AUDIT.md`**

### Je dois comprendre les workflows métier
→ Lire **`WORKFLOW_CALCUL_FACTURE_TGC.md`** et **`WORKFLOW_JOURNALISATION_FACTURATION.md`**

---

## Notes de réconciliation (run CLA-176 + SHIAAAAAAAAAAAAAAAAAAAAAAAA-502)

Le `.onboarding/` préexistait dans le checkout (jamais poussé sur le distant, run CLA-169 + CLA-176). Le présent run (SHIAAAAAAAAAAAAAAAAAAAAAAAA-502, étape 4 finale) a :

1. **Validé** l'état des artefacts d'analyse post-audit (SHA courant `192d047`)
2. **Mis à jour intégralement** les quatre documents de référence pour refléter :
   - Taux par ligne (limitaton « taux unique » levée, taux mixtes supportés)
   - Monolog 3.x (migré depuis 1.x)
   - Gardes InvalidArgumentException/OverflowException ajoutées
   - 12 tests (5 nominaux + 7 exceptions) couvrant InvoiceCalculator
   - AppLogger non testé (risque identifié à adresser)
   - Incohérences PHP 8.0/8.1 et composer.lock détectées et documentées
3. **Confirmé** l'absence de dérive dans le code source (audit réconciliation 2026-08-08)
4. **Indexé** l'ensemble des artefacts (27 fichiers) dans ce document

**Aucune modification du code source n'a eu lieu** — la documentation a été mise à jour pour synchroniser avec l'état post-audit du code.

---

## Notes de réconciliation — audits (2026-08-08, SHIAAAAAAAAAAAAAAAAAAAAAAAA-500)

Le code a évolué significativement entre le SHA `5f5c8ee` (première version des audits) et le SHA courant `7ef6351`. Les six audits ont été entièrement mis à jour pour refléter l'état courant du dépôt. Écarts principaux traités :

1. **Migration Monolog 1.x → 3.x** — `AppLogger` utilise désormais `info()`/`error()` ; `composer.json` déclare `^3.0`. Les références à l'API obsolète et au risque de migration ont été mises à jour.
2. **Gardes d'entrée ajoutées** — `isset` + `\InvalidArgumentException` dans `totalHorsTaxe` et `totalTtc` ; `\OverflowException` sur dépassement `PHP_INT_MAX`. Les dettes de robustesse correspondantes sont résolues.
3. **`totalTtc` par ligne** — le paramètre `bool $tauxReduit` a disparu ; chaque ligne porte un `taux?: float` optionnel avec repli sur `TGC_STANDARD`. La limitation « taux unique par facture » est résolue.
4. **12 tests** (était 3) — ajout de tests taux mixte, taux par défaut, InvalidArgumentException (5 cas), OverflowException (2 cas).
5. **PHP `>=8.1`** dans `composer.json` — incohérence détectée avec `README.md:7` qui annonce `>= 8.0`.

**Aucune modification du code source n'a eu lieu lors de cette réconciliation** — seuls les fichiers d'audit et l'index ont été mis à jour.

---

**Workspace** : `shift-pilot-php`  
**Branche** : `main`  
**SHA audit** : `7ef6351` (2026-08-08 SHIAAAAAAAAAAAAAAAAAAAAAAAA-500 — audits réconciliés)  
**SHA courant (HEAD)** : `192d047` (2026-08-08 SHIAAAAAAAAAAAAAAAAAAAAAAAA-500 — fix qualification VÉRIFIÉ_CODE/HYPOTHÈSE)  
**SHA documents** : mis à jour au SHA `192d047` (2026-08-08)  
**Date de dernière mise à jour documents** : 2026-08-08  
**Agent responsable** : Rédacteur (rediger-documents)
