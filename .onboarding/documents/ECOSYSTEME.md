# ECOSYSTEME — Projet T-PORTE1 (shift-pilot-go + shift-pilot-php)

> **Confiance : low**. Les deux workspaces couvrent chacun un domaine métier isolé et sont sans lien de code. Aucune intégration, dépendance mutuelle, ou chaîne de flux ne traverse les deux dépôts dans l'état actuel. Les relations entre eux sont **conceptuelles et futures** : une réservation (shift-pilot-go) qui aboutirait devrait déclencher une facturation (shift-pilot-php), mais ce flux n'existe ni dans le code ni dans la persistance. Voir « Questions ouvertes » pour les clarifications requises.

## Workspaces couverts

- **shift-pilot-go** — Bibliothèque Go implémentant une **logique pure de calcul de disponibilité et d'enregistrement de réservation sur un créneau unique** ([CDC_FONCTIONNEL.md, « Scope présent vs. futur »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md)). Expose trois fonctions pures (`Remaining`, `IsAvailable`, `Book`) sur un type unique (`Slot`). Aucune persistance, aucune dépendance externe, aucun point d'entrée HTTP ou CLI ([PROJECT_CONTEXT.md, « Caractéristiques techniques »](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md)).
- **shift-pilot-php** — Bibliothèque PHP de calcul de facturation avec taxe générale sur la consommation (TGC) polynésienne ([PROJECT_CONTEXT.md, « Nature du projet »](../../shift-pilot-php/.onboarding/documents/PROJECT_CONTEXT.md)). Expose deux classes (`InvoiceCalculator` pour le calcul, `AppLogger` pour la journalisation via Monolog 3.x). Aucune persistance, aucune dépendance métier externe, aucun point d'entrée HTTP ([PROJECT_CONTEXT.md, « Structure du dépôt »](../../shift-pilot-php/.onboarding/documents/PROJECT_CONTEXT.md)).

## Dépendances entre workspaces

**Actuellement : aucune dépendance détectée.**

Aucun fichier, module, endpoint, ou protocole partagé entre shift-pilot-go et shift-pilot-php n'existe dans les dépôts analysés (2026-08-09).

**Preuves amont** :
- **shift-pilot-go** ([CARTOGRAPHIE_CODE.md](../../shift-pilot-go/.onboarding/documents/CARTOGRAPHIE_CODE.md)) : 45 lignes métier, un seul type (`Slot`), trois fonctions, zéro dépendance transitoire.
- **shift-pilot-php** ([CARTOGRAPHIE_CODE.md](../../shift-pilot-php/.onboarding/documents/CARTOGRAPHIE_CODE.md)) : 2 classes (InvoiceCalculator, AppLogger), structure de facture interne en mémoire, aucune mention de Slot ou de réservation.
- Aucune cartographie PHP ne référence `shift-pilot-go` ni aucun concept de créneau.
- Aucune cartographie Go ne référence `shift-pilot-php` ni aucun concept de facturation.

Les deux dépôts sont des bibliothèques isolées sans point d'articulation visible dans le code ou la configuration.

### Flux transversaux attendus (non implémentés)

Si le projet T-PORTE1 évolue vers une application complète de réservation-et-facturation, les flux suivants deviendraient pertinents — mais **aucun d'entre eux n'existe aujourd'hui** :

#### Cas d'usage A : Réservation → Facturation (non implémenté)

**Scénario prospectif** : si une application orchestratrice coordonnait les deux bibliothèques, les étapes suivantes décriraient un flux réservation-à-facturation — mais **chaque étape est bâtie sur une HYPOTHÈSE ou une INCONNU**, car aucun orchestrateur n'existe dans le code présent.

**Déduction technique (étapes HYPOTHÉTIQUES)** :
1. (HYPOTHÈSE) Une **application orchestratrice** appelle `Book(slot, n)` pour enregistrer n places ([CDC_FONCTIONNEL.md, WF2](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md#wf2---réserver-des-places-sur-un-créneau)).
   - Preuve amont : [PROJECT_CONTEXT.md (shift-pilot-go)](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md) — « Aucun point d'entrée HTTP, CLI, ou interface utilisateur ».
   - **INCONNU** : qui est cet orchestrateur ? (application web, CLI, service worker, etc.)
   
2. (HYPOTHÈSE + INCONNU) L'orchestrateur récupère le prix unitaire de l'activité depuis une **source externe** (catalogue d'activités).
   - Preuve amont : [CDC_FONCTIONNEL.md (shift-pilot-go), « Scope présent vs. futur »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « ✗ Absent : catalogue d'activités ».
   - Preuve amont : [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q4](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Où vivent les définitions d'activités ? »
   - **INCONNU** : qui détient ce catalogue ? (base de données tierce, API, enregistrement local ?)

3. (HYPOTHÈSE) L'orchestrateur construit une `ligne` de facture : `{quantite: n, prixUnitaire: prix_par_place}` ([CDC_FONCTIONNEL.md (shift-pilot-php), R1](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md#r1--ligne-de-facture)).
   - Preuve amont : structure de ligne documentée dans shift-pilot-php.
   
4. (HYPOTHÈSE) L'orchestrateur appelle `InvoiceCalculator.totalTtc([ligne])` pour obtenir le montant TTC en francs CFP ([CDC_FONCTIONNEL.md (shift-pilot-php), Cas 2-4](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md#cas-2--calcul-du-ttc-au-taux-standard-par-ligne)).
   - Preuve amont : `InvoiceCalculator` implémenté et validé dans shift-pilot-php.

5. (HYPOTHÈSE + INCONNU) L'orchestrateur persiste le résultat de la facturation.
   - Preuve amont : [CDC_FONCTIONNEL.md (shift-pilot-go), « Risque résiduel »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « Aucune persistance : la valeur retournée […] est une valeur Go en mémoire ».
   - Preuve amont : [CDC_FONCTIONNEL.md (shift-pilot-php), « Données »](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Pas de persistance ».
   - **INCONNU** : où persister ? (base de données tierce, fichier, cache distribué ?)

**État** : **flux non implémenté**. Les deux bibliothèques existent sans orchestrateur.
- [PROJECT_CONTEXT.md (shift-pilot-go)](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md) — Aucun point d'entrée HTTP ou serveur.
- [PROJECT_CONTEXT.md (shift-pilot-php)](../../shift-pilot-php/.onboarding/documents/PROJECT_CONTEXT.md) — Pas de contrôleur web.

#### Cas d'usage B : Logging transverse (audit)

**Observation** : `AppLogger` (shift-pilot-php, [CDC_FONCTIONNEL.md, R9-R10](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md#r9--méthode--facture-émise)) enregistre les événements de facturation via Monolog 3.x.

**Scénario prospectif (HYPOTHÈSE)** : si une **application orchestratrice** coordonnait réservation + facturation, elle pourrait souhaiter un logging unifié pour audit.
- **INCONNU** : faudrait-il tracer aussi les appels à `Book` dans shift-pilot-go ?
- **INCONNU** : sous quelle forme (logs centralisés, événements structurés, événements) ?

**État** : **pas d'orchestration commune dans le code**.
- [PROJECT_CONTEXT.md (shift-pilot-go)](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md) — Shift-pilot-go n'expose aucune journalisation.
- [CDC_FONCTIONNEL.md (shift-pilot-php), R9-R10](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md#r9--méthode--facture-émise) — Shift-pilot-php journalise uniquement via `AppLogger.factureEmise()` et `AppLogger.erreurCalcul()`.
- Aucun code ne combine les deux.

## Structure pour l'intégration future

Pour que ces flux deviennent réels, il faudrait :

1. **Créer une couche d'application/orchestration** (HYPOTHÈSE) : ex. un service HTTP, une CLI, une goroutine concurrente qui importe les deux bibliothèques et orchestre les appels.
   - [PROJECT_CONTEXT.md (shift-pilot-go)](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md) — « Aucun point d'entrée HTTP, CLI, ou interface utilisateur n'existe ».
   - [PROJECT_CONTEXT.md (shift-pilot-php)](../../shift-pilot-php/.onboarding/documents/PROJECT_CONTEXT.md) — « pas de contrôleur web ».

2. **Implémenter la persistance** dans l'une ou l'autre, ou dans une couche tierce.
   - [CDC_FONCTIONNEL.md (shift-pilot-go), « Risque résiduel »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « les valeurs retournées par `Book` sont des valeurs Go en mémoire ».
   - [CDC_FONCTIONNEL.md (shift-pilot-php), « Données »](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Pas de persistance — les factures n'existent que pendant l'exécution ».

3. **Ajouter un catalogue d'activités avec pricing**.
   - [CDC_FONCTIONNEL.md (shift-pilot-go), « Scope présent vs. futur »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « ✗ Absent : catalogue d'activités ».
   - [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q4](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Où vivent les définitions d'activités ? ».

4. **Spécifier le modèle commercial** : qui paie, quand, quel montant pour quelle activité ?
   - [CDC_FONCTIONNEL.md (shift-pilot-go), Questions non résolues](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « Le projet doit-il fournir une fonction `Cancel` ? Clients/réservants : y aura-t-il une entité `Client` ? ».
   - [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — Aucune règle de paiement ou temporalité n'existe dans le code.

5. **Gérer les autres entités métier**.
   - **Clients/réservants** : [CDC_FONCTIONNEL.md (shift-pilot-go), « Acteurs »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — aucune notion, seul « code appelant » existe.
   - **Lignes multiples avec pricing** : [CDC_FONCTIONNEL.md (shift-pilot-php), R6](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md#r6--taux-par-ligne-depuis-mise-à-jour-2026-08-08) — supporte les taux mixtes mais aucun lien vers catalogue d'activités.
   - **Annulation de réservations** : [CDC_FONCTIONNEL.md (shift-pilot-go), Validation stricte de `n`](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — rejette `n ≤ 0`, pas de déréservation formalisée.

## Questions ouvertes

### 1. Modèle commercial global (**HYPOTHÈSE**)
- Une réservation d'activité chez T-PORTE1 entraîne-t-elle toujours une facturation ? Ou existe-t-il des modèles gratuits, prépayés, ou basés sur inscription ? (**INCONNU**)
  - Référence : [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q1](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Une réservation d'activité […] entraîne-t-elle toujours une facturation ? »
  
- Y a-t-il plusieurs activités avec des prix différents, ou un tarif unique ? (**INCONNU**)

- Qui est facturé : le réservant individuel, un gestionnaire de groupe, une entreprise ? (**INCONNU**)

- Quelle est la devise cible pour l'affichage/rapport ? (**HYPOTHÈSE**)
  - [CDC_FONCTIONNEL.md (shift-pilot-php), R8](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — supporte francs CFP uniquement, mais cette devise n'est pas sourcée dans le dépôt comme exigence métier — c'est une hypothèse implémentée.

### 2. Timing et décision (**HYPOTHÈSE**)
- La facturation doit-elle être immédiate après la réservation, ou différée (au début de l'activité, après-activité, fin de mois) ?
  - Référence : [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q2](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « La facturation doit-elle être immédiate ? »
  
- Faut-il un workflow d'annulation/remboursement formalisé dans shift-pilot-go avant intégration ? (**INCONNU**)
  - Preuve : [CDC_FONCTIONNEL.md (shift-pilot-go), Questions non résolues](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « Le projet doit-il fournir une fonction `Cancel` ou `Release` ? »
  - Preuve : [CDC_FONCTIONNEL.md (shift-pilot-go), Validation stricte de `n`](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — `Book` rejette `n ≤ 0` ; aucune déréservation implémentée.
  - **INCONNU** : une fonction `Cancel` ou `Release` est-elle requise ? Aucun code/domaine ne le définit.

### 3. Intégration technique (**INCONNU**)
- Qui orchestrera les deux bibliothèques ? (**INCONNU** : application web, CLI, service worker, autre ?)
  - Ni shift-pilot-go ni shift-pilot-php ne contiennent d'orchestrateur (voir Cas d'usage A).
  
- Où sera stockée la persistance : base de données unique, deux bases, fichier, cache distribué ? (**INCONNU**)
  - [PROJECT_CONTEXT.md (shift-pilot-go)](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md) — persistance absente, « responsabilité du code appelant ».
  - [CDC_FONCTIONNEL.md (shift-pilot-php), « Données »](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Pas de persistance ».
  
- Comment les deux seront-elles déployées : conteneurs séparés, même processus, monorepo ? (**INCONNU**)

- Y a-t-il une file d'attente (message broker) pour découpler la réservation de la facturation ? (**HYPOTHÈSE**, non implémentée)
  - Référence : [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q3](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Y a-t-il une file d'attente ? »

### 4. Catalogue et données partagées (**INCONNU**)
- Où vivent les définitions d'activités (nom, prix, durée, capacité max) ? (**INCONNU** — absent des deux dépôts)
  - [CDC_FONCTIONNEL.md (shift-pilot-go), « Scope présent vs. futur »](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « ✗ Absent : catalogue d'activités. […] `Activity` utilisée comme chaîne libre ».
  - [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q4](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Aucune entité `Activity` n'existe ».
  
- Comment shift-pilot-php accède-t-il au prix d'une activité pour facturer ? (**INCONNU** — aucun mécanisme visible)
  - [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q4](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Comment shift-pilot-php accède-t-il au prix ? Aucun mécanisme n'est visible ».
  
- La notion de « créneau » (`Slot` en Go) est-elle mappée dans shift-pilot-php ? (**INCONNU**)
  - [CDC_FONCTIONNEL.md (shift-pilot-go), Modèle de données](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — `Slot` avec `ID, Activity, Start, Capacity, Booked`.
  - [CDC_FONCTIONNEL.md (shift-pilot-php), Données](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — aucune notion de créneau ou de programmation.

### 5. Confiance dans les hypothèses métier (**HYPOTHÈSE**)
- Les taux TGC (16 % standard, 5 % réduit) de shift-pilot-php correspondent-ils à la réglementation polynésienne actuelle ? (**HYPOTHÈSE** — non sourcée)
  - [CDC_FONCTIONNEL.md (shift-pilot-php), R3, R4](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — taux appliqués mais « correspondance à la norme TGC polynésienne non sourcée dans le dépôt ».
  
- Le choix du franc CFP pour les prix est-il définitif, ou y aura-t-il une devise de change ? (**HYPOTHÈSE**)
  - [CDC_FONCTIONNEL.md (shift-pilot-php), Questions ouvertes Q1](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « Quelle est la devise cible ? ».
  
- Shift-pilot-go utilise des entiers pour `Booked/Capacity` ; shift-pilot-php utilise des entiers pour prix/quantité. Quelle est la plus petite unité monétaire ? (**INCONNU**)
  - [CDC_FONCTIONNEL.md (shift-pilot-go), Modèle de données](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — aucune contrainte de positivité sur `Capacity/Booked`.
  - [CDC_FONCTIONNEL.md (shift-pilot-php), R8](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — « francs CFP entiers — pas de centimes ni de sous-unité ».

### 6. Gestion des erreurs et logging (**HYPOTHÈSE**)
- Si `Book` réussit mais la facturation échoue, que se passe-t-il ? Rollback de la réservation ? (**INCONNU**)
  - [CDC_FONCTIONNEL.md (shift-pilot-go), Risque résiduel](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — aucune persistance ; l'appelant est responsable du stockage.
  - [CDC_FONCTIONNEL.md (shift-pilot-php), Limites de garantie](../../shift-pilot-php/.onboarding/documents/CDC_FONCTIONNEL.md) — l'écriture des logs dépend de la configuration Monolog externe.
  
- Shift-pilot-php journalise via Monolog ; shift-pilot-go n'expose pas de logging. Faut-il ajouter une journalisation structurée à shift-pilot-go pour audit ? (**HYPOTHÈSE**)
  - [CARTOGRAPHIE_CODE.md (shift-pilot-php)](../../shift-pilot-php/.onboarding/documents/CARTOGRAPHIE_CODE.md) — « AppLogger : adaptateur technique Monolog 3.x ».
  - [PROJECT_CONTEXT.md (shift-pilot-go)](../../shift-pilot-go/.onboarding/documents/PROJECT_CONTEXT.md) — « Aucun point d'entrée HTTP, CLI, ou interface utilisateur ».

### 7. Évolution future (**HYPOTHÈSE**)
- Shift-pilot-go reste-t-il une bibliothèque consommable, ou deviendra-t-il un service HTTP autonome ? (**INCONNU**)
  - [CDC_FONCTIONNEL.md (shift-pilot-go), Priorités prochaines](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « Clarifier la cible d'évolution ».
  
- Faut-il implémenter des fonctionnalités manquantes majeures (annulation, concurrence, validation du Slot à la construction) dans shift-pilot-go avant une intégration réelle ? (**INCONNU**)
  - [CDC_FONCTIONNEL.md (shift-pilot-go), Priorités prochaines](../../shift-pilot-go/.onboarding/documents/CDC_FONCTIONNEL.md) — « Enrichir les couches manquantes : catalogue d'activités, clients/réservants, annulation formalisée ».

## Résumé de l'état de l'écosystème

**Aujourd'hui** : deux noyaux isolés, chacun couvrant un sous-domaine minimal prouvé. Shift-pilot-go : réservation pure, sans persistance. Shift-pilot-php : facturation TGC pure, sans catalogue ni client. Aucune chaîne de valeur end-to-end, aucune orchestration.

**Demain (HYPOTHÈSE)** : pour constituer un produit de « réservation d'activités nautiques en Polynésie française avec facturation TGC », il faudrait :
- Créer une **application orchestratrice** (INCONNU : architecture cible non définie)
- **Ajouter la persistance** (INCONNU : où, comment, stratégie d'arrondi)
- **Modéliser un catalogue d'activités** (INCONNU : source de vérité, API)
- **Spécifier les workflows commerciaux** (annulation, paiement, notifications) — voir section « Questions ouvertes »

Aucun de ces étages supplémentaires n'existe dans le code actuel.

**Confiance** : **low**. Nous connaissons le contenu détaillé de chaque pilote ([PROJECT_CONTEXT.md](../../documents/PROJECT_CONTEXT.md) et [CDC_FONCTIONNEL.md](../../documents/CDC_FONCTIONNEL.md) pour shift-pilot-go ; documents équivalents pour shift-pilot-php), mais la façon dont ils s'assembleront dans un produit réel reste un blanc.

---

## Traçabilité des sources

Les affirmations structurantes de ce document ont été synthétisées à partir des artefacts amont de chaque dépôt :

### Dépôt shift-pilot-go (`main`, SHA: `128bcf5`)
- **PROJECT_CONTEXT.md** : nature du projet, caractéristiques techniques, périmètre, risques résiduels.
- **CDC_FONCTIONNEL.md** : acteurs, workflows techniques (WF1 disponibilité, WF2 réservation), règles métier, modèle de données, domaines absents, priorités prochaines.
- **CARTOGRAPHIE_CODE.md** : 2 fichiers productifs (45 lignes métier), 3 fonctions, zéro dépendance transitive.

### Dépôt shift-pilot-php (`main`, SHA: `7470fd9`)
- **PROJECT_CONTEXT.md** : nature du projet, structure du dépôt, domaines métier (Facturation TGC, Journalisation applicative).
- **CDC_FONCTIONNEL.md** : acteurs, parcours utilisateur et cas testés (calcul HT/TTC, taux mixte), règles métier (R1-R12), structure de facture, cas limites, critères d'acceptation.
- **CARTOGRAPHIE_CODE.md** : 2 classes PHP (87 lignes métier), 12 tests exécutables, structure logique.

**Date d'analyse** : 2026-08-09  
**Audits de référence** : 
- shift-pilot-go : ARCHITECTURE_AUDIT.md, FUNCTIONAL_AUDIT.md, CODE_HOTSPOTS_AUDIT.md, DATA_MODEL_AUDIT.md, SECURITY_ROBUSTNESS_AUDIT.md, TESTING_AUDIT.md
- shift-pilot-php : ARCHITECTURE_AUDIT.md, FUNCTIONAL_AUDIT.md, CODE_HOTSPOTS_AUDIT.md, DATA_MODEL_AUDIT.md, SECURITY_ROBUSTNESS_AUDIT.md, TESTING_AUDIT.md
