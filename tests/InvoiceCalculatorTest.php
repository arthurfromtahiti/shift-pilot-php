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
        $lignes = [['label' => 'Prestation', 'quantite' => 1, 'prixUnitaire' => 10000]];
        $this->assertSame(11600, $calc->totalTtc($lignes));
    }

    public function testTotalTtcTauxReduit(): void
    {
        $calc = new InvoiceCalculator();
        $lignes = [['label' => 'Produit première nécessité', 'quantite' => 1, 'prixUnitaire' => 10000]];
        $this->assertSame(10500, $calc->totalTtc($lignes, true));
    }

    public function testTotalHorsTaxeLigneSansPrixUnitaireLèveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $calc = new InvoiceCalculator();
        $calc->totalHorsTaxe([['label' => 'Prestation', 'quantite' => 1]]);
    }

    public function testTotalHorsTaxeLigneSansQuantiteLèveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $calc = new InvoiceCalculator();
        $calc->totalHorsTaxe([['label' => 'Prestation', 'prixUnitaire' => 10000]]);
    }

    public function testTotalTtcLigneMalforméeLèveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $calc = new InvoiceCalculator();
        $calc->totalTtc([['label' => 'Prestation', 'quantite' => 1]]);
    }
}
