<?php

namespace App\Tests;

use App\InvoiceCalculator;
use PHPUnit\Framework\TestCase;

final class InvoiceCalculatorTest extends TestCase
{
    public function testTotalHorsTaxe(): void
    {
        $calc = new InvoiceCalculator();
        $lignes = [
            ['label' => 'Prestation A', 'quantite' => 2, 'prixUnitaire' => 10000],
            ['label' => 'Prestation B', 'quantite' => 1, 'prixUnitaire' => 5000],
        ];
        $this->assertSame(25000, $calc->totalHorsTaxe($lignes));
    }

    public function testTotalTtcTauxStandard(): void
    {
        $calc = new InvoiceCalculator();
        $lignes = [['label' => 'Prestation', 'quantite' => 1, 'prixUnitaire' => 10000, 'taux' => InvoiceCalculator::TGC_STANDARD]];
        $this->assertSame(11600, $calc->totalTtc($lignes));
    }

    public function testTotalTtcTauxReduit(): void
    {
        $calc = new InvoiceCalculator();
        $lignes = [['label' => 'Produit première nécessité', 'quantite' => 1, 'prixUnitaire' => 10000, 'taux' => InvoiceCalculator::TGC_REDUIT]];
        $this->assertSame(10500, $calc->totalTtc($lignes));
    }

    public function testTotalTtcTauxMixte(): void
    {
        $calc = new InvoiceCalculator();
        $lignes = [
            ['label' => 'Prestation de service', 'quantite' => 1, 'prixUnitaire' => 10000, 'taux' => InvoiceCalculator::TGC_STANDARD],
            ['label' => 'Produit première nécessité', 'quantite' => 2, 'prixUnitaire' => 5000, 'taux' => InvoiceCalculator::TGC_REDUIT],
        ];
        // Ligne 1 : 10000 × 1.16 = 11600
        // Ligne 2 : 10000 × 1.05 = 10500
        // Total   : 22100
        $this->assertSame(22100, $calc->totalTtc($lignes));
    }

    public function testTotalTtcSansTauxUtiliseTauxStandard(): void
    {
        $calc = new InvoiceCalculator();
        $lignes = [['label' => 'Prestation', 'quantite' => 1, 'prixUnitaire' => 10000]];
        $this->assertSame(11600, $calc->totalTtc($lignes));
    }
}
