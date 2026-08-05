# PROJECT.md — shift-pilot-php

Contrat de maintenance SHIFT. Champs initiaux renseignés via l'interaction Paperclip CLA-182 (2026-08-04). Champs `php_version` et `write_channel` mis à jour via interaction c8e9e753, répondue le 2026-08-05.

## Runtime

```yaml
php_version: na_pilot         # pilote de test sans serveur de production réel
                               # confirmé via interaction c8e9e753 (2026-08-05)
php_version_source: user_confirmation_2026-08-05
```

## Canal d'écriture Git → prod

```yaml
write_channel: na_pilot       # pilote de test sans déploiement réel
                               # confirmé via interaction c8e9e753 (2026-08-05)
```

## Couverture Git

```yaml
git_coverage:
  source_code: true            # src/ versionné dans Git (observé)
  composer_lock: true          # composer.lock présent sur feat/cla-16-generate-composer-lock
                               # (non mergé en main au 2026-08-05)
  ci_cd: false                 # aucun .github/workflows/ détecté (observé)
```

## Mises à jour autorisées

```yaml
maintenance:
  composer: true
```
