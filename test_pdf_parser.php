<?php

require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

try {
    $parser = new Parser();
    $pdf = $parser->parseFile('test_payslip.pdf');
    $text = $pdf->getText();

    echo "TESTO ESTRATTO DAL PDF:\n";
    echo "======================\n";
    echo $text;
    echo "\n======================\n";

    // Salva in un file per analisi
    file_put_contents('payslip_text.txt', $text);
    echo "Testo salvato in payslip_text.txt\n";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
