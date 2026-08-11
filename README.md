# shift-pilot-php

Pilote de test SHIFT/Paperclip — facturation avec TGC (Polynésie française).

## Stack

- PHP >= 8.1, Composer
- Monolog (journalisation)
- PHPUnit (tests) : `composer install` puis `composer test`

## Remarques

- Le fichier `composer.lock` est versionné pour garantir la reproductibilité des dépendances.
- La journalisation repose sur l'API Monolog 3.x (méthodes `info()` et `error()`).
