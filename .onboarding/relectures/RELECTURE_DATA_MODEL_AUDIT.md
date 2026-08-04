# Relecture — DATA_MODEL_AUDIT.md

## Verdict global
À corriger — l'axe général est juste, mais deux constats structurants sortent du périmètre de preuve : l'audit transforme des effets runtime supposés et un fait réglementaire externe en `VÉRIFIÉ_CODE`. Pour un audit de modèle, c'est bloquant.

## Problèmes bloquants

- `.onboarding/audits/DATA_MODEL_AUDIT.md:17` affirme en `VÉRIFIÉ_CODE` qu'un appel avec `['qty' => 2, 'price' => 10000]` "produirait un résultat de 0". Depuis `src/InvoiceCalculator.php:19-22`, on peut prouver l'absence de validation des clés ; le résultat exact à l'exécution ne peut pas être promu en fait sans observation.
- `.onboarding/audits/DATA_MODEL_AUDIT.md:19` présente les constantes TGC comme "des taux fiscaux réglementaires (Polynésie française)". Dans le dépôt, `src/InvoiceCalculator.php:11-12` prouve seulement les valeurs `0.16` et `0.05`. Le rattachement réglementaire est un contexte externe, non sourcé dans l'artefact.

## Problèmes mineurs

- `.onboarding/audits/DATA_MODEL_AUDIT.md:21` décrit une coercition silencieuse d'un `float` vers `int`. Là encore, l'absence de validation d'entrée est prouvée, pas le comportement exact sans exécution.

## Points vérifiés et corrects

- L'absence de persistance est correctement établie par l'inventaire `rg --files .` et la lecture de `src/InvoiceCalculator.php`.
- Le rôle central du docblock `@param array<array{...}>` dans `src/InvoiceCalculator.php:13-15` est bien identifié.
- La limitation à un taux global par appel est correctement déduite de `src/InvoiceCalculator.php:25-29`.

## Recommandations de correction

- Ramener les exemples `qty` / `price` et `prixUnitaire: 9999.5` au statut `HYPOTHÈSE`, ou les supprimer si l'audit ne peut pas les prouver.
- Reformuler le point TGC en restant au niveau local : "le dépôt encode deux taux nommés `TGC_STANDARD` et `TGC_REDUIT`" ; si le lien réglementaire est utile, l'annoncer comme contexte externe.
