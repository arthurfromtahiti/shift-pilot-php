<?php

namespace App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Journalisation applicative — utilise l'API Monolog 1.x (addInfo/addError).
 */
class AppLogger
{
    private Logger $logger;

    public function __construct(string $canal = 'facturation', string $fichier = 'php://stderr')
    {
        $this->logger = new Logger($canal);
        $this->logger->pushHandler(new StreamHandler($fichier));
    }

    public function factureEmise(int $totalTtc): void
    {
        $this->logger->addInfo('Facture émise', ['total_ttc' => $totalTtc]);
    }

    public function erreurCalcul(string $message): void
    {
        $this->logger->addError('Erreur de calcul', ['detail' => $message]);
    }
}
