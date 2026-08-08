# Sécurité & Robustesse — Audit

> Confiance : high — les fichiers source sont courts (≤ 57 lignes chacun) et ont été lus intégralement. Les risques de runtime (comportement Monolog sur flux inaccessible) restent des hypothèses, marquées comme tels. Audit de réconciliation confronté au code courant (2026-08-08).

## Compréhension globale

Le dépôt est une bibliothèque PHP sans couche réseau : pas d'exposition HTTP, pas d'authentification, pas d'accès base de données. La surface d'attaque reste réduite à ce que l'application hôte injecte dans les méthodes publiques. Par rapport à la première version auditée, deux défenses ont été ajoutées : les gardes `isset` avec `\InvalidArgumentException` sur les clés de ligne, et la détection d'overflow via `\OverflowException`. Les risques résiduels sont des comportements non défensifs ponctuels : absence de rejet de valeurs négatives, constructeur `AppLogger` sans `try/catch`, et paramètre `$fichier` non validé.

## Résumé exécutif

Aucun secret en dur, aucune donnée personnelle, aucune requête SQL, aucun appel réseau — la surface sécurité reste très faible. Les deux évolutions positives depuis la première version : les accès `$ligne['quantite']`/`$ligne['prixUnitaire']` sont maintenant protégés par `isset()` et lèvent une `\InvalidArgumentException` documentée (`src/InvoiceCalculator.php:23-25`, `45-47`) ; un dépassement de `PHP_INT_MAX` lève une `\OverflowException` dans les deux méthodes (`src/InvoiceCalculator.php:29-31`, `52-54`). Ces corrections éliminent le risque de résultat financier silencieusement faussé sur les deux cas les plus critiques. Trois points de robustesse subsistent : aucun rejet de valeurs négatives, instanciation de `StreamHandler` sans `try/catch` dans `AppLogger`, et paramètre `$fichier` non validé.

## Constats détaillés

**Gardes sur les clés de tableau — ajoutées.** `VÉRIFIÉ_CODE` — `src/InvoiceCalculator.php:23-25` et `45-47` vérifient désormais `isset($ligne['quantite'], $ligne['prixUnitaire'])` avant tout accès et lèvent `\InvalidArgumentException('Chaque ligne doit contenir "quantite" et "prixUnitaire".')` si une clé est absente. Le docblock des deux méthodes documente ces exceptions (`src/InvoiceCalculator.php:16-17`, `39-40`). Les tests correspondants sont présents (`tests/InvoiceCalculatorTest.php:54-87`). Ce risque de la première version est résolu.

**Détection d'overflow — ajoutée.** `VÉRIFIÉ_CODE` — `src/InvoiceCalculator.php:28-31` (dans `totalHorsTaxe`) et `51-54` (dans `totalTtc`) vérifient si `round($total) > PHP_INT_MAX || round($total) < PHP_INT_MIN` et lèvent `\OverflowException`. Ce cas est testé (`tests/InvoiceCalculatorTest.php:89-101`). La bibliothèque ne peut plus produire silencieusement un entier incorrect par troncation ou wrapping.

**Absence de validation des valeurs négatives.** `VÉRIFIÉ_CODE` — aucun rejet de `quantite` ou `prixUnitaire` négatif dans `src/InvoiceCalculator.php:21-33` ni dans `src/InvoiceCalculator.php:43-56`. `HYPOTHÈSE` : une ligne `['label' => 'Avoir', 'quantite' => -1, 'prixUnitaire' => 10000]` produirait un HT de `-10000` sans erreur — résultat déduit par lecture statique de l'absence de garde, non observé à l'exécution. Si ce cas représente un avoir licite, il devrait être documenté ; s'il doit être rejeté, une garde est manquante.

**`StreamHandler` sans gestion d'erreur.** `VÉRIFIÉ_CODE` — `src/AppLogger.php:18` : `$this->logger->pushHandler(new StreamHandler($fichier))` sans `try/catch` visible dans le source. `HYPOTHÈSE` : si la destination `$fichier` est inaccessible (droits insuffisants, chemin inexistant), Monolog 3.x pourrait lever une exception qui remonterait sans interception jusqu'à l'application hôte — comportement non observé à l'exécution.

**Paramètre `$fichier` non validé.** `VÉRIFIÉ_CODE` — `src/AppLogger.php:15` : le constructeur accepte `string $fichier` quelconque sans validation ni restriction. L'appelant peut passer un chemin arbitraire fourni par configuration externe. `HYPOTHÈSE` : dans un contexte où la destination de log serait pilotée par une configuration non contrôlée, la bibliothèque fournirait une écriture vers une destination arbitraire sans contrôle — le scénario exact d'exploitation dépend du contexte d'appel, non observable depuis le dépôt seul.

**Aucun secret en dur.** `VÉRIFIÉ_CODE` — aucune valeur de secret (password, token, key, api) dans le source. Seules des constantes métier (`TGC_STANDARD = 0.16`, `TGC_REDUIT = 0.05`) sont codées en dur dans `src/InvoiceCalculator.php:11-12`.

**Pas d'injection, pas de XSS, pas de SQL.** `VÉRIFIÉ_CODE` — la bibliothèque n'effectue aucune requête SQL, aucun appel réseau, aucun rendu HTML. Les vecteurs d'injection classiques (SQLi, XSS, SSRF) sont inexistants dans ce périmètre.

**`$message` libre dans `erreurCalcul`.** `VÉRIFIÉ_CODE` — `src/AppLogger.php:27-28` : le message d'erreur est transmis tel quel dans le contexte Monolog (`['detail' => $message]`). Aucun filtre ni longueur maximale. `HYPOTHÈSE` : dans un contexte où les logs sont centralisés et indexés (ELK, Loki), un message très long ou contenant des caractères de contrôle pourrait poser des problèmes d'indexation ou de mémoire. Risque faible dans le contexte actuel bibliothèque.

## Forces

- `VÉRIFIÉ_CODE` : gardes `isset` + `\InvalidArgumentException` sur les clés de ligne — `src/InvoiceCalculator.php:23-25`, `45-47` — défense explicite, documentée dans les docblocks et testée.
- `VÉRIFIÉ_CODE` : `\OverflowException` sur dépassement `PHP_INT_MAX` — `src/InvoiceCalculator.php:29-31`, `52-54` — résultat financier jamais silencieusement tronqué.
- `VÉRIFIÉ_CODE` : aucune donnée personnelle, aucun secret, aucun credential dans le source (`src/InvoiceCalculator.php`, `src/AppLogger.php`, `composer.json`, `phpunit.xml`).
- `VÉRIFIÉ_CODE` : aucun appel réseau, aucune requête SQL, aucune exécution de commande système — surface d'attaque réseau nulle.
- `VÉRIFIÉ_CODE` : les constantes TGC sont codées dans la classe et non dans un fichier de configuration externe — évite la modification accidentelle d'un taux fiscal par une variable d'environnement mal protégée.

## Dettes techniques

- `VÉRIFIÉ_CODE` : aucune validation des valeurs numériques (négatifs non rejetés) dans `src/InvoiceCalculator.php:21-33`, `43-56`.
- `VÉRIFIÉ_CODE` : `StreamHandler` instancié sans `try/catch` (`src/AppLogger.php:18`) — `HYPOTHÈSE` : exception possible si le flux est inaccessible, comportement non observé à l'exécution.
- `VÉRIFIÉ_CODE` : paramètre `$fichier` du constructeur `AppLogger` non validé (`src/AppLogger.php:15`).

## Zones critiques

- **`src/AppLogger.php:15-18`** — constructeur avec chemin de fichier libre et sans gestion d'erreur. `HYPOTHÈSE` : si la destination est fournie par une configuration externe non contrôlée, cela pourrait constituer une écriture vers une destination arbitraire — impact conditionnel au contexte de déploiement hôte.

## Risques

- `VÉRIFIÉ_CODE` : **aucun rejet des valeurs négatives** (`src/InvoiceCalculator.php:21-33`) — une ligne avec `quantite` ou `prixUnitaire` négatif produit un total négatif sans signal d'erreur. `HYPOTHÈSE` : si ce comportement n'est pas intentionnel (avoir), cela constitue un risque de résultat financier incorrect non détecté — aucun test de ce cas dans `tests/InvoiceCalculatorTest.php`.
- `HYPOTHÈSE` : **exception non interceptée à l'instanciation d'AppLogger** si le chemin passé en paramètre est invalide ou inaccessible. Impact : l'application hôte reçoit une exception non documentée au moment de la création du logger, pas du calcul. Preuve : `src/AppLogger.php:18` (absence de try/catch observable).

## Recommandations priorisées

1. **Documenter ou valider le comportement sur valeurs négatives** — si les avoirs (lignes à `quantite` ou `prixUnitaire` négatif) sont licites, les documenter dans le docblock et les tester ; sinon, lever une `\InvalidArgumentException` explicite. Fichier : `src/InvoiceCalculator.php:21-33`.
2. **Entourer `new StreamHandler()` d'un `try/catch`** dans le constructeur d'`AppLogger` et retransmettre une exception applicative documentée plutôt que de laisser remonter l'exception interne de Monolog. Fichier : `src/AppLogger.php:18`.
3. **Documenter le comportement sur tableau vide** (`totalHorsTaxe([])` retourne `0`) comme contrat de la bibliothèque — soit l'accepter (avec test), soit le rejeter (avec validation). Fichier : `src/InvoiceCalculator.php`, `README.md`.

## Questions ouvertes

- Les valeurs négatives (avoirs, remises) sont-elles un cas métier licite ou à rejeter ?
- Le paramètre `$fichier` du constructeur d'`AppLogger` est-il destiné à recevoir des valeurs issues d'une configuration externe ? Si oui, une liste blanche de destinations est recommandée.
- La bibliothèque sera-t-elle exposée à des données d'entrée non contrôlées (ex. données reçues d'une API externe) ? La réponse change le niveau de priorité des gardes d'entrée.
