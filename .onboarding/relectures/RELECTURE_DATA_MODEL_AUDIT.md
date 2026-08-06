# Relecture — DATA_MODEL_AUDIT.md

## Verdict global
À corriger — l'axe général est juste, mais deux constats structurants sortent du périmètre de preuve : l'audit transforme des effets runtime supposés et un fait réglementaire externe en `VÉRIFIÉ_CODE`. Pour un audit de modèle, c'est bloquant.

## Problèmes bloquants

- `.onboarding/audits/DATA_MODEL_AUDIT.md` décrivait une coercition silencieuse d'un `float` vers `int`. Cette hypothèse a été corrigée par le fix SHIAAAAAAAAAAAAAAAAAAAAAAAA-394 qui ajoute explicitement `(int) round()` aux deux méthodes, éliminant toute coercition silencieuse.

## Problèmes mineurs

## Points vérifiés et corrects

- L'absence de persistance est correctement établie par l'inventaire `rg --files .` et la lecture de `src/InvoiceCalculator.php`.
- Le rôle central du docblock `@param array<array{...}>` dans `src/InvoiceCalculator.php:13-15` est bien identifié.
- La limitation à un taux global par appel est correctement déduite de la signature de `totalTtc()` (ligne 35) avec le paramètre `bool $tauxReduit`.

## Recommandations de correction

- Ramener les exemples `qty` / `price` et `prixUnitaire: 9999.5` au statut `HYPOTHÈSE`, ou les supprimer si l'audit ne peut pas les prouver.
- Reformuler le point TGC en restant au niveau local : "le dépôt encode deux taux nommés `TGC_STANDARD` et `TGC_REDUIT`" ; si le lien réglementaire est utile, l'annoncer comme contexte externe.
