# PROJECT.md — shift-pilot-php

Contrat de maintenance SHIFT. Champs renseignés via l'interaction Paperclip CLA-182 (2026-08-04).

## Runtime

```yaml
php_version: unknown          # réponse interaction : "Inconnue / à vérifier"
php_version_source: unknown   # non fourni
```

> **Action requise avant merge** : confirmer que PHP ≥ 8.1 tourne en production.
> `monolog/monolog ^3.0` exige PHP ≥ 8.1 — si le serveur cible est encore sur PHP 8.0,
> Composer refusera l'installation.

## Canal d'écriture Git → prod

```yaml
write_channel: unknown        # réponse interaction : "Inconnu"
```

> **Action requise avant merge** : confirmer qu'un mécanisme de déploiement Git → prod existe
> (CI/CD automatique ou déploiement manuel déclenché depuis ce dépôt).

## Couverture Git

```yaml
git_coverage:
  source_code: true           # src/ versionné dans Git (observé)
  composer_lock: false        # composer.lock absent du dépôt (observé)
  ci_cd: false                # aucun .github/workflows/ détecté (observé)
```

## Mises à jour autorisées

```yaml
maintenance:
  composer: true
```
