# Modèle de données — Audit

> Confiance : high — le dépôt ne contient ni ORM ni schéma de base de données ; toute la « structure de données » est observable directement dans le source. Aucun accès base fourni (les deux domaines sont marqués `Dépend de la base : non` dans la carte).

## Compréhension globale

`shift-pilot-php` ne possède ni modèle de données persistant, ni ORM, ni entité, ni migration. La seule structure de données du domaine est la **ligne de facture**, représentée comme un tableau associatif PHP passé en paramètre. Cette représentation est documentée par un docblock mais pas appliquée par le typage PHP. Le modèle de données est donc entièrement implicite et non enforced — ce qui est acceptable pour un pilote bibliothèque à deux classes, mais constitue la principale source de fragilité si le périmètre s'élargit.

## Résumé exécutif

Il n'y a pas de couche de persistance à auditer : pas de base de données, pas de migrations, pas de schéma, pas d'ORM. La structure `{label: string, quantite: int, prixUnitaire: int}` est documentée dans le docblock de `totalHorsTaxe` (`src/InvoiceCalculator.php:15`) mais n'est pas appliquée par un type PHP natif ni une classe de valeur. Le code accède directement aux clés du tableau sans garde, ce qui fait de la cohérence du tableau entrant la seule barrière entre un appel correct et un résultat erroné. Les constantes de taux (`TGC_STANDARD`, `TGC_REDUIT`) sont les seules données de référence figées dans le code — leurs noms et valeurs évoquent des taux fiscaux réglementaires (`HYPOTHÈSE` contextuelle, non sourcée dans le dépôt). La devise CFP entière (pas de centimes) est une règle métier documentée dans le docblock de `totalHorsTaxe` (`src/InvoiceCalculator.php:15` : `prix en francs CFP`) et confirmée par les valeurs entières des tests — mais elle n'est pas enforced dans le type système.

## Constats détaillés

**Absence totale de persistance.** `VÉRIFIÉ_CODE` — l'arbre `origin/main` ne contient aucun fichier ORM (Doctrine, Eloquent, ActiveRecord), aucune migration, aucun schéma SQL, aucun fichier `.env` de connexion. Les méthodes `totalHorsTaxe` et `totalTtc` calculent et retournent ; elles ne lisent ni n'écrivent en base (`src/InvoiceCalculator.php:17-31`). La carte des domaines confirme : `Dépend de la base : non` pour les deux domaines.

**Ligne de facture = tableau associatif, sans type natif.** `VÉRIFIÉ_CODE` — la structure d'une ligne est documentée exclusivement par le docblock `@param array<array{label: string, quantite: int, prixUnitaire: int}>` (`src/InvoiceCalculator.php:15`). PHP n'enforces pas ce docblock à l'exécution : un appel avec `['qty' => 2, 'price' => 10000]` (clés anglaises par erreur) passerait sans erreur de type. `HYPOTHÈSE` : dans ce cas, la boucle accéderait à des clés absentes et pourrait produire un résultat faussé — le comportement runtime exact (valeur retournée, signal d'erreur) n'a pas été observé à l'exécution. Il n'existe pas de classe `LigneDeFacture` ou `InvoiceLine` qui encapsulerait cette structure et garantirait sa cohérence à la construction.

**Constantes TGC comme données de référence.** `VÉRIFIÉ_CODE` — le dépôt encode deux constantes nommées `TGC_STANDARD = 0.16` et `TGC_REDUIT = 0.05` (`src/InvoiceCalculator.php:11-12`). Leur durcissement en constantes de classe est un choix défensif correct — toute évolution du taux nécessite une modification explicite du code, pas d'un fichier de config ou d'une entrée base non versionnée. `HYPOTHÈSE` (contexte externe, non sourcé dans le dépôt) : ces valeurs semblent correspondre aux taux de la TGC polynésienne — ce rattachement réglementaire ne peut être confirmé que par une source externe, pas par le seul code.

**Règle monétaire : entiers, francs CFP.** `VÉRIFIÉ_CODE` — les types de retour sont `int` (`src/InvoiceCalculator.php:17,26`), le docblock de `totalHorsTaxe` mentionne explicitement `prix en francs CFP` (`src/InvoiceCalculator.php:15`), et les données de test utilisent des valeurs entières en francs CFP (`10000`, `5000`, `25000`, `11600`, `10500`). Cette règle (pas de centimes, pas de virgule) est donc documentée dans le code source, mais non enforced par le type système. Si un prix unitaire décimal était passé par erreur (ex. `prixUnitaire: 9999.5`), `HYPOTHÈSE` : PHP accepterait la valeur, la multiplication produirait un float, et le retour `int` de `totalHorsTaxe` pourrait entraîner une coercition silencieuse — le comportement exact (troncature, arrondi) n'a pas été observé à l'exécution.

**Taux de TGC unique par facture.** `VÉRIFIÉ_CODE` — le paramètre `bool $tauxReduit` (`src/InvoiceCalculator.php:26`) applique un taux unique à l'ensemble du total HT. La ligne de facture ne porte pas de taux individuel. `HYPOTHÈSE` : si des produits à taux différents coexistent dans la même facture, le modèle actuel ne peut pas les représenter correctement — il faudrait soit panacher en appelant séparément `totalTtc` pour chaque sous-ensemble, soit remodéliser la ligne pour inclure un champ `tauxReduit: bool`.

## Forces

- `VÉRIFIÉ_CODE` : absence de couche de persistance — aucune migration à gérer, aucun schéma à synchroniser, aucun ORM à configurer. Simplicité maximale pour un pilote.
- `VÉRIFIÉ_CODE` : les constantes de taux TGC sont localisées en un seul endroit (`src/InvoiceCalculator.php:11-12`) — un changement réglementaire se fait en une seule modification.
- `VÉRIFIÉ_CODE` : les types de retour `int` (`src/InvoiceCalculator.php:17,26`) expriment explicitement la règle CFP (entiers), même si elle n'est pas défendue à l'entrée.

## Dettes techniques

- `VÉRIFIÉ_CODE` : absence de value object `LigneDeFacture` — la structure de données est implicite dans un docblock, non enforced par le type système PHP (`src/InvoiceCalculator.php:15`).
- `HYPOTHÈSE` : règle monétaire (pas de centimes) non défendue à l'entrée — un `float` passé en `prixUnitaire` produirait une coercition silencieuse.
- `VÉRIFIÉ_CODE` : modèle de taux unique par facture — limitation structurelle qui interdit les factures panachant taux standard et taux réduit (`src/InvoiceCalculator.php:26-30`).

## Zones critiques

- **Structure de la ligne de facture** (`src/InvoiceCalculator.php:15-22`) — c'est la donnée d'entrée critique. Toute évolution du modèle (ajout d'un champ `tauxReduit` par ligne, introduction d'un avoir, d'une remise) impacte directement ce point. Un senior regarderait ici en premier avant d'ajouter une fonctionnalité.

## Risques

- `VÉRIFIÉ_CODE` : **absence de garde sur les clés de la ligne** (`src/InvoiceCalculator.php:21`) — un tableau passé avec des clés incorrectes ou manquantes (`qty` au lieu de `quantite`) n'est pas rejeté. `HYPOTHÈSE` : le comportement runtime (valeur du résultat, nature du signal d'erreur) peut conduire à un résultat financier incorrect sans signal explicite — non observé à l'exécution, aucun test sur ce cas.
- `HYPOTHÈSE` : **limitation taux mixtes** — une facture commerciale réelle peut inclure des lignes à 16 % et des lignes à 5 % ; le modèle actuel (`bool $tauxReduit` global) interdit cette représentation. Si ce besoin émerge, il implique une remodelisation non triviale de la signature des méthodes et de la structure de ligne. Preuve : `src/InvoiceCalculator.php:26`.

## Recommandations priorisées

1. **Introduire une classe ou un type `LigneDeFacture`** (readonly class PHP 8.2, ou au minimum une fonction de validation) qui garantisse la présence et le type des champs `label`, `quantite`, `prixUnitaire` à la construction. Fichier : `src/InvoiceCalculator.php` + nouveau fichier `src/LigneDeFacture.php`.
2. **Documenter explicitement la règle monétaire CFP** (entiers en francs, pas de centimes) dans le README ou un docblock de classe, et considérer un rejet explicite (exception) si un float est passé en `prixUnitaire` ou `quantite`. Fichier : `src/InvoiceCalculator.php`, `README.md`.
3. **Évaluer le besoin de taux par ligne** avant la prochaine évolution fonctionnelle — le modèle actuel est un choix, pas un oubli ; mais s'il doit changer, c'est une rupture d'interface. Fichier : `src/InvoiceCalculator.php:26`.

## Questions ouvertes

- La limitation à un taux unique par facture est-elle une règle métier confirmée ou une simplification du pilote ?
- Les avoirs (lignes à quantité négative) sont-ils prévus ?
- Le type `int` pour les prix est-il garanti par le domaine (francs CFP sans centime) ou un héritage de la conception du pilote ?
