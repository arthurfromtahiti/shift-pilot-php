<?php

namespace App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Journalisation applicative — API Monolog 3.x (info/error).
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
        $this->logger->info('Facture émise', ['total_ttc' => $totalTtc]);
    }

    public function erreurCalcul(string $message): void
    {
        $this->logger->error('Erreur de calcul', ['detail' => $message]);
    }
}
