<?php

namespace Tests\Unit;

use Dompdf\Dompdf;
use Dompdf\Options;
use Tests\TestCase;

class ReceiptPaperTest extends TestCase
{
    public function test_receipt_pdf_keeps_a4_portrait_paper(): void
    {
        $dompdf = new Dompdf(new Options(['defaultFont' => 'Arial']));
        $dompdf->loadHtml('<!DOCTYPE html><html><body>Kwitansi</body></html>');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $this->assertEqualsWithDelta(595.2756, $dompdf->getCanvas()->get_width(), 0.01);
        $this->assertEqualsWithDelta(841.8898, $dompdf->getCanvas()->get_height(), 0.01);
    }
}
