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
| audit | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `audits/ARCHITECTURE_AUDIT.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Architecture minimale, pas d'orchestration interne, risque migration Monolog 2.x, pas de `composer.lock` |
| audit | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `audits/DATA_MODEL_AUDIT.md` | 2026-08-04 | 5f5c8ee | établi | Pas de persistance, pas d'ORM, structures en mémoire seule, pas de base de données |
| audit | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `audits/FUNCTIONAL_AUDIT.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Périmètre cohérent mais étroit, limitation taux mixte, aucun test d'intégration, hypothèse conformité TGC polynésienne |
| audit | — | — | shift-pilot-php | `audits/CODE_HOTSPOTS_AUDIT.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Points chauds : constantes TGC, pas de validation, API Monolog 1.x, arrondi non documenté |
| audit | — | — | shift-pilot-php | `audits/SECURITY_ROBUSTNESS_AUDIT.md` | 2026-08-04 | 5f5c8ee | établi | Aucune injection, pas d'accès réseau, pas d'accès fichier hors stderr, pas de secret ; risques mineurs sur validation |
| audit | — | — | shift-pilot-php | `audits/TESTING_AUDIT.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | 3 tests nominaux couverts, `AppLogger` non testé, pas de couverture configurée, reproductibilité compromise sans `composer.lock` |

---

## Artefacts de référence (étape 4)

### Documents de référence

| Type | Domaine(s) | Workflow | Dépôt | Fichier | Date | Version SHA | Niveau de preuve | Titre |
|---|---|---|---|---|---|---|---|---|
| document | — | — | shift-pilot-php | `documents/PROJECT_CONTEXT.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Contexte métier, structure du dépôt, domaines, périmètre couvert/absent, fragilités repérées, charge de travail |
| document | facturation-tgc, journalisation-applicative | — | shift-pilot-php | `documents/CDC_FONCTIONNEL.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Cahier des charges : contexte, acteurs, parcours utilisateur, 12 règles métier, données, cas limites documentés |
| document | — | — | shift-pilot-php | `documents/CARTOGRAPHIE_CODE.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Navigation dans le code source : deux classes, constantes TGC, dépendances Composer, risques et dettes identifiés |
| document | — | — | shift-pilot-php | `documents/CAHIER_RECETTE.md` | 2026-08-04 | 5f5c8ee | contient une hypothèse | Plan de test : 4 cas PHPUnit couverts, 4 cas limites à adresser, 4 tests infra, scénarios d'intégration (app hôte) |

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
| **Code source lu** | high | Intégralité de `src/` vérifiée, 32 et 30 lignes |
| **Règles métier identifiées** | high | 12 règles documentées, préuvées ou questionnées |
| **Architecture comprise** | high | Deux classes, zéro dépendance croisée, une fragilité Monolog 1.x |
| **Couverture de test** | medium | 3 tests nominaux, `AppLogger` non testé, cas limites non couverts |
| **Conformité réglementaire** | low | Taux TGC supposés polynésiens, non sourcés ; mode d'arrondi non documenté |
| **Reproductibilité des builds** | low | Pas de `composer.lock`, Monolog peut varier |

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

## Notes de réconciliation (run CLA-176)

Le `.onboarding/` préexistait dans le checkout (jamais poussé sur le distant, run CLA-169). Le présent run (CLA-176, étape 4) a :

1. **Validé** que tous les artefacts d'analyse (domaines, workflows, audits) restent exacts au SHA courant (`5f5c8ee`)
2. **Produit** les quatre documents de référence (PROJECT_CONTEXT, CDC_FONCTIONNEL, CARTOGRAPHIE_CODE, CAHIER_RECETTE)
3. **Confirmé** l'absence de dérive dans le code source
4. **Indexé** l'ensemble des artefacts (22 fichiers) dans ce document

**Aucune modification du code source n'a eu lieu** — la documentation est une pure synthèse.

---

**Workspace** : `shift-pilot-php`  
**Branche** : `main`  
**SHA référence** : `5f5c8ee00765beb04be08b5bcb089066c36a0f30` (HEAD = origin/main)  
**Date de dernière génération** : 2026-08-04  
**Agent responsable** : Rédacteur (rediger-documents)
