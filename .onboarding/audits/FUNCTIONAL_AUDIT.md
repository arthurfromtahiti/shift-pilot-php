# Fonctionnel — Audit

> Confiance : high sur le périmètre visible (code source + tests) — medium sur les intentions métier (pas d'accès aux spécifications fonctionnelles au-delà du README et des docblocks).

## Compréhension globale

`shift-pilot-php` implémente un sous-ensemble très restreint de la facturation TGC : calcul HT/TTC avec deux taux fixes, journalisation des événements. Il n'y a ni génération de document PDF, ni persistance, ni notion de client ou de fournisseur, ni gestion de remises ou d'avoirs. Le README présente explicitement le dépôt comme un *pilote de test* — ce périmètre est donc vraisemblablement intentionnel. L'audit fonctionnel mesure la cohérence entre ce que le code fait et ce qu'il prétend faire, et identifie les fonctionnalités manquantes susceptibles de bloquer un passage en production.

## Résumé exécutif

Le périmètre fonctionnel livré est **cohérent avec lui-même** : les méthodes font ce que leurs signatures et leurs docblocks annoncent, les constantes TGC présentent des valeurs cohérentes avec les taux couramment cités pour la Polynésie française (hypothèse contextuelle — non sourcée dans le dépôt), et les tests valident le chemin nominal. Mais plusieurs limitations réduisent l'exploitabilité réelle de la bibliothèque hors d'un contexte de pilote. La plus significative est l'incapacité à panacher des taux TGC différents sur la même facture : le paramètre `bool $tauxReduit` s'applique à l'ensemble du total HT, sans possibilité de désigner par ligne quel taux s'applique. Fonctionnellement, une facture commerciale réelle incluant à la fois des produits à 16 % et des produits de première nécessité à 5 % ne peut pas être représentée. Les deux classes ne sont jamais intégrées dans le code visible du dépôt : `InvoiceCalculator` et `AppLogger` coexistent sans qu'aucun exemple ou code d'orchestration ne montre comment les combiner — cette intégration est entièrement déléguée à l'application hôte, sans documentation ni test.

## Constats détaillés

**Cohérence calcul HT/TTC.** `VÉRIFIÉ_CODE` — `totalHorsTaxe` retourne la somme des `quantite × prixUnitaire` pour chaque ligne, protégée par `(int) round()` (`src/InvoiceCalculator.php:21-27`) ; `totalTtc` applique le même processus avec le taux sélectionné via `(int) round($ht * (1 + $taux))` (`src/InvoiceCalculator.php:38-45`). Les deux méthodes appliquent désormais la même garde d'arrondi. Les trois tests valident ce calcul (`25000`, `11600`, `10500`) et les résultats sont arithmétiquement corrects. Aucune incohérence entre l'implémentation et son intention déclarée.

**Taux TGC codés en constantes.** `VÉRIFIÉ_CODE` — le dépôt encode `TGC_STANDARD = 0.16` (16 %) et `TGC_REDUIT = 0.05` (5 %) (`src/InvoiceCalculator.php:11-12`). Leur codage en constantes de classe garantit une source de vérité unique et visible. `HYPOTHÈSE` (contexte externe, non sourcé dans le dépôt) : ces valeurs sembleraient correspondre aux taux de la TGC polynésienne — le rattachement réglementaire exact (décret, date d'entrée en vigueur) ne peut pas être établi depuis le seul code. `HYPOTHÈSE` : si ces taux étaient modifiés réglementairement, la modification du code serait nécessaire — aucune configuration externe ne permet de les surcharger.

**Limitation : taux unique par facture.** `VÉRIFIÉ_CODE` — la signature `totalTtc(array $lignes, bool $tauxReduit = false)` applique un seul taux à l'intégralité du HT calculé sur toutes les lignes. Il est impossible, avec l'API actuelle, de calculer le TTC d'une facture dont certaines lignes sont à 16 % et d'autres à 5 %. L'appelant doit alors appeler `totalTtc` deux fois (une fois par sous-ensemble de lignes) et sommer les résultats — ce qui n'est ni documenté ni testé. Cette limitation est architecturale : elle est inscrite dans la signature de la méthode.

**Pas d'intégration visible entre les deux classes.** `VÉRIFIÉ_CODE` — aucun fichier dans le dépôt (`src/`, `tests/`, racine) ne montre un exemple d'appel combinant `InvoiceCalculator::totalTtc` et `AppLogger::factureEmise`. Les noms de méthodes (`factureEmise`, `erreurCalcul`) évoquent un usage séquentiel, mais aucun code ni documentation ne prouve ou n'illustre ce flux dans le dépôt. `HYPOTHÈSE` : l'intégration est prévue côté application hôte — mais en l'absence d'exemple, un développeur découvrant la bibliothèque n'a pas de modèle d'utilisation.

**Pas de génération de document, pas de persistance.** `VÉRIFIÉ_CODE` — `InvoiceCalculator` ne génère ni PDF, ni JSON, ni structure de facture persistée. Il calcule et retourne des entiers. C'est cohérent avec la description « bibliothèque de calcul » du README, mais le terme « facturation » dans le nom du projet peut créer une attente fonctionnelle plus large chez un utilisateur non averti.

**Mode d'arrondi : comportement par défaut non documenté.** `VÉRIFIÉ_CODE` — `round()` est appelé sans second argument (mode explicite) dans `src/InvoiceCalculator.php:27` et `src/InvoiceCalculator.php:45`. `HYPOTHÈSE` (contexte langage, non observable depuis le seul dépôt) : PHP utiliserait `PHP_ROUND_HALF_UP` par défaut. La règle d'arrondi applicable à la TGC polynésienne n'est pas documentée dans le dépôt. `HYPOTHÈSE` : si la règle fiscale requiert un mode différent pour certaines valeurs limites, le résultat serait fonctionnellement incorrect sur ces cas.

**Comportement sur tableau vide.** `VÉRIFIÉ_CODE` — `totalHorsTaxe([])` retourne `0` (la boucle ne s'exécute pas, `(int) round(0) = 0`) ; `totalTtc([])` retourne `0` (applique le taux à 0, `(int) round(0) = 0`). Ce comportement est techniquement cohérent mais n'est pas documenté comme cas métier attendu. `HYPOTHÈSE` : une facture vide devrait peut-être lever une erreur fonctionnelle plutôt que retourner 0 F CFP, selon les règles métier.

## Forces

- `VÉRIFIÉ_CODE` : cohérence totale entre les signatures, les docblocks et l'implémentation — aucun comportement caché ou divergent du contrat déclaré.
- `VÉRIFIÉ_CODE` : les deux taux TGC sont nommés et localisés en constantes de classe (`src/InvoiceCalculator.php:11-12`) — leur valeur est lisible et centralisée. `HYPOTHÈSE` (contexte externe) : ces valeurs sembleraient correspondre aux taux TGC polynésiens — non sourcé dans le dépôt.
- `VÉRIFIÉ_CODE` : la bibliothèque est sans effet de bord — pas de persistance, pas d'écriture de fichier (hors journalisation sur stderr), pas d'appel réseau — ce qui la rend facilement testable et prévisible.

## Dettes techniques

- `VÉRIFIÉ_CODE` : limitation taux par facture — impossible de panacher 16 % et 5 % sur la même facture avec l'API actuelle (`src/InvoiceCalculator.php:26`).
- `HYPOTHÈSE` : absence d'exemple d'utilisation combinée — aucun code n'illustre le workflow complet (calcul + log) dans le dépôt.
- `HYPOTHÈSE` : mode d'arrondi non documenté — conformité avec la règle fiscale CFP non prouvée pour les cas limites.

## Zones critiques

- **`src/InvoiceCalculator.php:26`** — la signature `bool $tauxReduit` est le point d'architecture qui interdit les factures à taux mixtes. Toute évolution fonctionnelle vers ce besoin nécessite un changement d'interface publique.
- **Absence de code d'intégration** — un développeur qui reprend la bibliothèque pour l'intégrer dans une application n'a pas de point de départ visible dans le dépôt.

## Risques

- `VÉRIFIÉ_CODE` + `HYPOTHÈSE` : **limitations fonctionnelles non documentées** — un consommateur de la bibliothèque qui ne lit pas le source ne saura pas que les factures multi-taux ne sont pas supportées. Si les consommateurs ont besoin de factures multi-taux, l'API actuelle appliquera un taux unique (16 % avec `$tauxReduit = false`) à l'ensemble sans signaler la limitation — sans erreur, sans avertissement.
- `HYPOTHÈSE` : **mode d'arrondi non conforme** — si la réglementation CFP impose un arrondi différent de `PHP_ROUND_HALF_UP` pour certaines valeurs, la bibliothèque produit des montants incorrects sur ces cas. Le risque est faible pour un pilote ; il devient réel si la bibliothèque sert à des factures légalement opposables.

## Recommandations priorisées

1. **Documenter les limitations fonctionnelles dans le README** — en particulier : taux unique par facture, pas de remise par ligne, pas d'avoir, pas de génération de document. Cela définit un contrat fonctionnel explicite pour les consommateurs. Fichier : `README.md`.
2. **Ajouter un exemple d'utilisation** dans le README ou un fichier dédié (`examples/`) montrant le flux complet : construction de lignes, appel `totalTtc`, appel `factureEmise`. C'est la documentation minimale pour un composant bibliothèque. Fichier : `README.md` ou `examples/usage.php`.
3. **Clarifier le comportement sur tableau vide et valeurs négatives** — soit documenter comme cas licite (0 F CFP retourné), soit lever une `\InvalidArgumentException` documentée. Fichier : `src/InvoiceCalculator.php`, `README.md`.
4. **Documenter le mode d'arrondi** — référencer la règle réglementaire CFP applicable, ou noter que `PHP_ROUND_HALF_UP` est utilisé par défaut faute de spécification. Fichier : `src/InvoiceCalculator.php:27,45` (appels `(int) round()`).

## Questions ouvertes

- Les factures à taux mixtes (lignes à 16 % et lignes à 5 % dans la même facture) sont-elles un besoin réel pour les consommateurs cibles, ou la limitation taux-unique est-elle une règle métier confirmée ?
- Le mode d'arrondi `PHP_ROUND_HALF_UP` est-il conforme à la réglementation fiscale TGC polynésienne pour les montants en francs CFP ?
- La bibliothèque est-elle destinée à évoluer vers une génération de document de facture (PDF, JSON structuré), ou restera-t-elle un composant de calcul pur ?
- Qui sont les consommateurs prévus (application métier interne, API exposée, autre bibliothèque) ? La réponse détermine le niveau de documentation et de validation d'entrée nécessaire.
