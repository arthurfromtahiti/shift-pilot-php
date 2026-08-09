# Relecture — documents de référence, étape 4

## Verdict global

**À CORRIGER.** La correction de la phrase sur les clés manquantes est présente : elle se limite désormais à la preuve statique et marque l'exécution runtime `À OBSERVER`. Toutefois le CDC conserve plusieurs formulations qui présentent des tests comme déjà exécutés, alors que le cahier de recette indique explicitement que PHPUnit n'a pas été lancé/observé.

## Problèmes bloquants

1. **Garanties de test encore excessives dans `CDC_FONCTIONNEL.md`.** La phrase des clés manquantes est corrigée, mais les cinq parcours (lignes 75, 88, 101, 115 et 128) disent encore que les tests « retournent » leurs résultats, et R6/les critères d'acceptation parlent d'une capacité « implémentée et testée ». `CAHIER_RECETTE.md` prouve seulement la présence et la lecture statique des tests ; l'exécution runtime est `À OBSERVER`. Reformuler ces preuves en « assertion présente/arithmétiquement cohérente ; exécution runtime non observée » et réserver « observé », « retourne » ou « testé » à un run PHPUnit consigné.

2. **Contradiction documentaire explicitement conservée.** `README.md` dit encore que `composer.lock` n'est pas versionné et annonce PHP 8.0, tandis que Git et Composer prouvent respectivement un lock suivi et PHP >=8.1. Les documents d'étape 4 signalent désormais cette divergence comme dette à corriger ; ce n'est donc plus une incohérence silencieuse du corpus, mais la correction de README reste à faire hors de cette étape. Les références PHPUnit sont maintenant alignées sur 10.5.x/10.5.64.

3. **Statuts de recette : correction substantielle, vigilance mineure.** Le cahier n'emploie plus `PASS` comme statut constaté ; les occurrences restantes sont des sorties attendues ou des champs à renseigner par le testeur. Ce point est considéré corrigé.

4. **Garantie Monolog : correction constatée.** Le CDC borne maintenant explicitement l'affirmation aux appels statiquement observés et renvoie la gestion d'erreurs au handler externe. Ce point est considéré corrigé.

## Points vérifiés

- Le taux par ligne et le mélange 16 % / 5 % sont traçables à `src/InvoiceCalculator.php:41-55` et aux tests nommés.
- Les clés réellement gardées (`quantite`, `prixUnitaire`) sont mieux distinguées de `label`, qui n’est pas consommée par le calcul.
- L’absence de test `AppLogger` et la dépendance à la configuration Monolog sont explicitement signalées.
- Les documents marquent désormais l’absence d’exécution runtime dans plusieurs emplacements, ce qui corrige partiellement le défaut de qualification antérieur.

## Corrections demandées

- Stabiliser et committer l’état documentaire avant de publier un SHA de référence.
- Corriger les formulations d'exécution dans `CDC_FONCTIONNEL.md` (parcours, R6 et critères) pour ne revendiquer que la preuve statique tant que `composer test` n'est pas exécuté et consigné.
- Corriger ensuite `README.md` pour supprimer les déclarations obsolètes PHP 8.0 et lock non versionné ; conserver entre-temps cette divergence explicitement signalée.
- Remplacer les `PASS` récapitulatifs non exécutés par des statuts non ambigus.
- Supprimer toute garantie d’absence d’exception Monolog non démontrée par une exécution ; conserver une formulation descriptive et qualifier l’observation manquante.
