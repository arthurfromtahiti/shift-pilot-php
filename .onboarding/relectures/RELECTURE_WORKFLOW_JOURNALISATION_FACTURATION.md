# Relecture — WORKFLOW_JOURNALISATION_FACTURATION.md

## Verdict global
Bon — la version courante borne correctement son propos à la façade locale `AppLogger`. Les délégations vers Monolog, les valeurs par défaut et l'absence d'intégration prouvée avec `InvoiceCalculator` sont décrites sans transformer un câblage visible en effet runtime certain.

## Problèmes bloquants

Aucun.

## Problèmes mineurs

Aucun.

## Points vérifiés et corrects

- Les points d'entrée sont exacts : `__construct(string $canal = 'facturation', string $fichier = 'php://stderr')`, `factureEmise(int $totalTtc): void` et `erreurCalcul(string $message): void` existent dans `src/AppLogger.php:15-28`.
- Les deux chemins sont fidèles à la façade locale : `factureEmise()` délègue à `addInfo('Facture émise', ['total_ttc' => $totalTtc])` dans `src/AppLogger.php:21-23`, et `erreurCalcul()` délègue à `addError('Erreur de calcul', ['detail' => $message])` dans `src/AppLogger.php:26-28`.
- Les formulations finales restent au bon niveau de preuve : le workflow parle d'un `StreamHandler` configuré vers `php://stderr` par défaut (`src/AppLogger.php:15-18`) et qualifie l'écriture effective / le rendu final comme dépendants du comportement interne de Monolog, non observable dans ce dépôt.
- La confiance `medium` est honnêtement bornée à `AppLogger` : absence de tests dédiés, aucun appelant visible et aucune preuve d'orchestration avec `InvoiceCalculator`, confirmé par `rg -n "AppLogger|factureEmise|erreurCalcul|InvoiceCalculator|totalTtc|totalHorsTaxe" .`.
- La dépendance Monolog est bien sourcée : `use Monolog\Logger; use Monolog\Handler\StreamHandler;` dans `src/AppLogger.php:5-6` et `monolog/monolog: ^1.25` dans `composer.json:6-8`.
- Les risques et questions ouvertes restent honnêtes : comportement du `StreamHandler`, sérialisation du contexte et migration éventuelle de version sont explicitement laissés au rang de limites ou d'hypothèses faute de preuve locale.

## Recommandations de correction

Aucune.
