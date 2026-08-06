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
     * @throws \InvalidArgumentException si une ligne est absente de "quantite" ou "prixUnitaire"
     * @throws \OverflowException si le total dépasse PHP_INT_MAX ou est inférieur à PHP_INT_MIN
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
        $rounded = round($total);
        if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) {
            throw new \OverflowException('Le total hors taxe dépasse les bornes de PHP_INT_MAX.');
        }
        return (int) $rounded;
    }

    /**
     * @param array<array{label: string, quantite: int, prixUnitaire: int, taux?: float}> $lignes
     *        Chaque ligne peut porter son propre taux TGC (clé "taux") ; défaut : TGC_STANDARD.
     * @throws \InvalidArgumentException si une ligne est absente de "quantite" ou "prixUnitaire"
     * @throws \OverflowException si le total dépasse PHP_INT_MAX ou est inférieur à PHP_INT_MIN
     */
    public function totalTtc(array $lignes): int
    {
        $total = 0.0;
        foreach ($lignes as $ligne) {
            if (!isset($ligne['quantite'], $ligne['prixUnitaire'])) {
                throw new \InvalidArgumentException('Chaque ligne doit contenir "quantite" et "prixUnitaire".');
            }
            $taux = $ligne['taux'] ?? self::TGC_STANDARD;
            $total += $ligne['quantite'] * $ligne['prixUnitaire'] * (1 + $taux);
        }
        $rounded = round($total);
        if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) {
            throw new \OverflowException('Le total TTC dépasse les bornes de PHP_INT_MAX.');
        }
        return (int) $rounded;
    }
}
