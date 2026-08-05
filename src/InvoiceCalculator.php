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
            $total += $ligne['quantite'] * $ligne['prixUnitaire'];
        }
        return $total;
    }

    /**
     * @param array<array{label: string, quantite: int, prixUnitaire: int, taux?: float}> $lignes
     *        Chaque ligne peut porter son propre taux TGC (clé "taux") ; défaut : TGC_STANDARD.
     */
    public function totalTtc(array $lignes): int
    {
        $total = 0.0;
        foreach ($lignes as $ligne) {
            $taux = $ligne['taux'] ?? self::TGC_STANDARD;
            $total += $ligne['quantite'] * $ligne['prixUnitaire'] * (1 + $taux);
        }
        return (int) round($total);
    }
}
