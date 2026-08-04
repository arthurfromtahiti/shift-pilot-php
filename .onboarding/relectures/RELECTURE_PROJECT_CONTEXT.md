# Relecture — PROJECT_CONTEXT.md

## Verdict global
À corriger — le document est globalement utile et exploite une partie de la matière amont, mais il contient plusieurs affirmations présentées comme acquises alors qu'elles relèvent d'une hypothèse ou d'une recommandation. En l'état, la frontière entre preuve du dépôt et extrapolation n'est pas assez nette.

## Problèmes bloquants
- `API Monolog 1.x en fin de vie` est affirmé comme fait dans `PROJECT_CONTEXT.md` ("Points clés", point 3), alors que la carte des domaines ne permettait que la formulation `HYPOTHÈSE` sur un éventuel retrait en Monolog 2.x ; preuve amont : `.onboarding/domaines/CARTE_DES_DOMAINES.md` requalifie explicitement ce point en hypothèse externe, source code limitée à `src/AppLogger.php:9` et `composer.json:8`.
- `Une ligne malformée peut produire un calcul incorrect silencieusement` est formulé comme effet établi dans `PROJECT_CONTEXT.md` ("Points clés", point 4), alors que l'amont ne prouve qu'un accès sans garde et laisse le comportement runtime en `HYPOTHÈSE` ; preuves : `.onboarding/workflows/WORKFLOW_CALCUL_FACTURE_TGC.md` section "Risques", `src/InvoiceCalculator.php:19-21`.

## Problèmes mineurs
- `C'est une limitation architecturale, pas un oubli` sur le taux unique par facture est une interprétation forte. L'amont prouve la limitation via `src/InvoiceCalculator.php:26-30`, mais pas l'intention "pas un oubli" ; preuve amont consultée : `.onboarding/workflows/WORKFLOW_CALCUL_FACTURE_TGC.md`.
- `Aucun code du dépôt n'orchestre les deux` est correct, mais la phrase qui suit (`l'intégration complète ... revient entièrement à l'application hôte`) gagnerait à être marquée comme déduction ; preuves : absence d'appelant dans l'arbre, `.onboarding/workflows/WORKFLOW_JOURNALISATION_FACTURATION.md` signale explicitement l'absence d'orchestration observée.

## Points vérifiés et corrects
- Le cadrage "bibliothèque PHP pure", sans HTTP ni persistance, est traçable à `.onboarding/domaines/CARTE_DES_DOMAINES.md` et au code (`src/InvoiceCalculator.php`, `src/AppLogger.php`, `composer.json`, `README.md`).
- La séparation entre calcul TGC et journalisation est fidèle à la carte des domaines et à `CARTOGRAPHIE_CODE.md`.
- Les zones d'attention sur le taux unique, l'absence d'interface pour `AppLogger` et l'arrondi non documenté reprennent bien des constats amont.

## Recommandations de correction
- Repasser toutes les formulations de type obsolescence Monolog, effet runtime sur clé manquante, ou intention d'architecture en `HYPOTHÈSE` si elles ne sont pas prouvées par le code ou par un audit amont.
- Préférer `le dépôt ne montre pas...` à `le système fait/ne fait pas...` dès qu'il s'agit d'un comportement d'intégration non observé.
- Conserver le caractère synthétique du document, mais durcir la discipline de preuve sur les risques techniques.
