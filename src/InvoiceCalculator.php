<?php

namespace App;

/**
 * Calcul de factures avec TGC (taxe générale sur la consommation, Polynésie française).
 * Taux standard : 16 %. Taux réduit : 5 %.
 */
class InvoiceCalculator
{
    public const TGC_STANDARD = 0.16;
    public const TGC_REDUIT = 0.05;

    /**
     * @param array<array{label: string, quantite: int, prixUnitaire: int}> $lignes prix en francs CFP
     */
    public function totalHorsTaxe(array $lignes): int
    {
        $total = 0;
        foreach ($lignes as $ligne) {
            if (!isset($ligne['quantite'], $ligne['prixUnitaire'])) {
                throw new \InvalidArgumentException('Chaque ligne doit contenir "quantite" et "prixUnitaire".');
            }
            $total += $ligne['quantite'] * $ligne['prixUnitaire'];
        }
        return $total;
    }

    public function totalTtc(array $lignes, bool $tauxReduit = false): int
    {
        $ht = $this->totalHorsTaxe($lignes);
        $taux = $tauxReduit ? self::TGC_REDUIT : self::TGC_STANDARD;
        return (int) round($ht * (1 + $taux));
    }
}
