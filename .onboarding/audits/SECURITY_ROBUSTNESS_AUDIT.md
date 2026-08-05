# Sécurité & Robustesse — Audit

> Confiance : high — les fichiers source sont courts (≤ 32 lignes chacun) et ont été lus intégralement. Les risques de runtime (comportement PHP sur clé manquante, comportement Monolog sur flux inaccessible) restent des hypothèses, marquées comme tels.

## Compréhension globale

Le dépôt est une bibliothèque PHP sans couche réseau : pas d'exposition HTTP, pas d'authentification, pas d'accès base de données. La surface d'attaque est donc réduite à ce que l'application hôte injecte dans les méthodes publiques. Les risques principaux ne sont pas des failles d'intrusion classiques mais des **comportements non défensifs** : accès à des clés de tableau non garanties, absence de validation des valeurs entrantes, et une API de dépendance obsolète dont le remplacement rompt silencieusement.

## Résumé exécutif

Aucun secret en dur, aucune donnée personnelle, aucune requête SQL, aucun appel réseau — la surface sécurité est très faible. Les risques observés sont de robustesse, pas d'intrusion. **`InvoiceCalculator` accède aux clés avec garde en place (CLA-280, SHA b53b03e) : un `isset()` est appliqué avant l'accès, levant une `\InvalidArgumentException` si une clé est absente — RÉSOLU.** Aucune valeur négative n'est rejetée — `VÉRIFIÉ_CODE` : aucune garde sur les négatifs n'est présente dans `src/InvoiceCalculator.php:18-35`. `AppLogger` instancie `StreamHandler` sans `try/catch` — `VÉRIFIÉ_CODE` : l'absence de bloc `try/catch` est observable dans `src/AppLogger.php:18` ; `HYPOTHÈSE` : si la destination est inaccessible, une exception pourrait remonter sans interception jusqu'à l'application hôte — comportement non observé à l'exécution. Ces défauts restent modérés dans un contexte bibliothèque où l'appelant est censé être maîtrisé, mais le premier a été adressé.

## Constats détaillés

**Accès aux clés de tableau sans garde — RÉSOLU.** `VÉRIFIÉ_CODE` (CLA-280, SHA b53b03e) — `src/InvoiceCalculator.php:22` : un garde `isset($ligne['quantite'], $ligne['prixUnitaire'])` a été ajouté avant l'accès aux clés, levant une `\InvalidArgumentException` explicite si l'une est absente. Le PHPDoc de `totalHorsTaxe` a également été enrichi d'une annotation `@throws \InvalidArgumentException` (SHA 3935220). Les quatre cas limites sont maintenant testés dans `tests/InvoiceCalculatorTest.php:34-60` (`testTotalHorsTaxeLigneSansPrixUnitaireLèveException`, `testTotalHorsTaxeLigneSansQuantiteLèveException`, `testTotalTtcLigneMalforméeLèveException`, `testTotalHorsTaxeLigneSansAucuneClésRequises`).

**Absence de validation des valeurs (quantités et prix négatifs).** `VÉRIFIÉ_CODE` — aucun test ni aucune garde dans `src/InvoiceCalculator.php:17-31` ne rejette une quantité ou un prix unitaire négatif. Une ligne `['label' => 'Avoir', 'quantite' => -1, 'prixUnitaire' => 10000]` produirait un HT de `-10000` sans erreur, puis un TTC négatif. Si ce cas doit représenter un avoir, il devrait être explicitement documenté comme comportement intentionnel ; s'il ne doit pas être possible, il devrait être rejeté.

**`StreamHandler` sans gestion d'erreur.** `VÉRIFIÉ_CODE` — `src/AppLogger.php:18` : `$this->logger->pushHandler(new StreamHandler($fichier))` est appelé sans `try/catch` visible dans le source. `HYPOTHÈSE` : si la destination `$fichier` est inaccessible (droits insuffisants, chemin inexistant), Monolog pourrait lever une exception qui remonterait sans interception jusqu'à l'application hôte. Pour `php://stderr` (valeur par défaut), le risque est probablement faible dans la plupart des environnements — le comportement exact selon la destination n'a pas été observé à l'exécution.

**Paramètre `$fichier` non validé.** `VÉRIFIÉ_CODE` — `src/AppLogger.php:15` : le constructeur accepte un `string $fichier` quelconque sans validation ni restriction. L'appelant peut passer un chemin arbitraire fourni par configuration externe. Pour un pilote à usage interne où l'appelant est maîtrisé, le risque est faible. `HYPOTHÈSE` : dans un contexte où la destination de log serait pilotée par une configuration non contrôlée, la bibliothèque fournirait une écriture vers une destination arbitraire sans contrôle — le scénario exact d'exploitation dépend du contexte d'appel, non observable depuis le dépôt seul.

**Aucun secret en dur.** `VÉRIFIÉ_CODE` — recherche sur l'ensemble du dépôt (`grep -r` sur `password`, `secret`, `token`, `key`, `api`) : aucune valeur de secret n'est présente dans le source. Seules des constantes métier (`TGC_STANDARD = 0.16`, `TGC_REDUIT = 0.05`) sont codées en dur dans `src/InvoiceCalculator.php:11-12` — ce sont des taux fiscaux publics, pas des secrets.

**Pas d'injection, pas de XSS, pas de SQL.** `VÉRIFIÉ_CODE` — la bibliothèque n'effectue aucune requête SQL, aucun appel réseau, aucun rendu HTML. Les vecteurs d'injection classiques (SQLi, XSS, SSRF) sont inexistants dans ce périmètre.

**`$message` libre dans `erreurCalcul`.** `VÉRIFIÉ_CODE` — `src/AppLogger.php:26-28` : le message d'erreur est transmis tel quel dans le contexte Monolog (`['detail' => $message]`). Aucun filtre ni longueur maximale. `HYPOTHÈSE` : dans un contexte où les logs sont centralisés et indexés (ELK, Loki), un message très long ou contenant des caractères de contrôle pourrait poser des problèmes d'indexation ou de mémoire. Risk faible dans le contexte actuel bibliothèque.

## Forces

- `VÉRIFIÉ_CODE` : aucune donnée personnelle, aucun secret, aucun credential dans le source (`src/InvoiceCalculator.php`, `src/AppLogger.php`, `composer.json`, `phpunit.xml`).
- `VÉRIFIÉ_CODE` : aucun appel réseau, aucune requête SQL, aucune exécution de commande système — surface d'attaque réseau nulle.
- `VÉRIFIÉ_CODE` : les constantes TGC sont codées dans la classe et non dans un fichier de configuration externe — évite la modification accidentelle d'un taux fiscal par une variable d'environnement mal protégée.

## Dettes techniques

- `VÉRIFIÉ_CODE` : aucune validation des valeurs numériques (négatifs non rejetés) dans `src/InvoiceCalculator.php:18-35`.
- `VÉRIFIÉ_CODE` : `StreamHandler` instancié sans `try/catch` (`src/AppLogger.php:18`) — `HYPOTHÈSE` : exception possible si le flux est inaccessible, non observé à l'exécution. **Déporté en backlog CLA-297.**

## Zones critiques

- **`src/InvoiceCalculator.php:21-26`** — boucle sur les lignes : garde en place pour les clés (SHA b53b03e), mais aucun rejet des valeurs négatives. `VÉRIFIÉ_CODE` : l'absence de validation pour les négatifs est observable dans le source. `HYPOTHÈSE` : si la bibliothèque est un jour consommée par une couche recevant des données externes (API, form) avec avoirs, cette zone devrait être documentée ou validée — le comportement runtime exact n'a pas été observé.
- **`src/AppLogger.php:15-18`** — constructeur avec chemin de fichier libre et sans gestion d'erreur. Un chemin arbitraire fourni par une configuration externe constituerait une écriture vers une destination non contrôlée. **Déporté en backlog CLA-297.**

## Risques

- **Accès à clés manquantes — RÉSOLU (CLA-280, SHA b53b03e)** : la garde `isset()` est en place dans `src/InvoiceCalculator.php:22`, et quatre tests couvrent les cas limites (`tests/InvoiceCalculatorTest.php:34-60`). Une `\InvalidArgumentException` est levée immédiatement (fail-fast) plutôt que produire un total silencieusement faux.
- `HYPOTHÈSE` : **exception non interceptée à l'instanciation d'AppLogger** si le chemin passé en paramètre est invalide ou inaccessible. Impact : l'application hôte reçoit une exception non documentée au moment de la création du logger, pas du calcul. Preuve : `src/AppLogger.php:18` (absence de try/catch observable). **Déporté en backlog CLA-297.**

## Recommandations priorisées

1. **[RÉALISÉ — CLA-280]** Valider les clés dans `totalHorsTaxe` — un garde `isset()` a été implémenté (SHA b53b03e) levant une `\InvalidArgumentException` explicite, couvert par quatre tests de validation (`tests/InvoiceCalculatorTest.php:34-60`). Pas d'action supplémentaire.
2. **Entourer `new StreamHandler()` d'un `try/catch`** dans le constructeur d'`AppLogger` et retransmettre une exception applicative documentée, plutôt que de laisser remonter l'exception interne de Monolog. Fichier : `src/AppLogger.php:18`.
3. **Documenter le comportement sur tableau vide et valeurs négatives** comme contrat de la bibliothèque — soit les accepter (avec test), soit les rejeter (avec validation + test). Fichier : `src/InvoiceCalculator.php`, `tests/InvoiceCalculatorTest.php`.

## Questions ouvertes

- Les valeurs négatives (avoirs, remises) sont-elles un cas métier licite ou à rejeter ?
- Le paramètre `$fichier` du constructeur d'`AppLogger` est-il destiné à recevoir des valeurs issues d'une configuration externe ? Si oui, une liste blanche de destinations est recommandée.
- La bibliothèque sera-t-elle exposée à des données d'entrée non contrôlées (ex. données reçues d'une API externe) ? La réponse change le niveau de priorité des gardes d'entrée.
