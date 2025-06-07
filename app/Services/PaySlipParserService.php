<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaySlip;
use App\Models\Salary;
use App\Models\User;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PaySlipParserService
{
    private Parser $pdfParser;
    private SalaryService $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->pdfParser = new Parser();
        $this->salaryService = $salaryService;
    }

    public function parsePaySlip(PaySlip $paySlip): bool
    {
        try {
            $filePath = storage_path('app/public/' . $paySlip->file_path);

            if (!file_exists($filePath)) {
                $paySlip->markAsError('File non trovato');
                return false;
            }

            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();

            // Log del testo estratto per debugging
            Log::info('Testo estratto dal PDF', [
                'pay_slip_id' => $paySlip->id,
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 500),
            ]);

            $extractedData = $this->extractDataFromText($text);

            Log::info('Dati estratti dal parsing', [
                'pay_slip_id' => $paySlip->id,
                'extracted_data' => $extractedData,
            ]);

            if (empty($extractedData)) {
                $paySlip->markAsError('Impossibile estrarre dati dal PDF. Formato non riconosciuto o PDF scansionato.');
                return false;
            }

            // Determina mese e anno dalla data o dal nome file
            $period = $this->extractPeriod($text, $paySlip->file_name);
            $extractedData['month'] = $period['month'];
            $extractedData['year'] = $period['year'];

            $paySlip->markAsProcessed($extractedData);

            // Aggiorna il record PaySlip con mese e anno
            $paySlip->update([
                'month' => $extractedData['month'],
                'year' => $extractedData['year'],
            ]);

            // Crea automaticamente il record Salary
            $this->createSalaryFromPaySlip($paySlip, $extractedData);

            return true;
        } catch (\Exception $e) {
            Log::error('Errore nel parsing della busta paga', [
                'pay_slip_id' => $paySlip->id,
                'error' => $e->getMessage(),
            ]);

            $paySlip->markAsError('Errore nel parsing: ' . $e->getMessage());
            return false;
        }
    }

    private function extractDataFromText(string $text): array
    {
        $data = [];

        // Normalizza il testo per facilitare l'estrazione
        $text = $this->normalizeText($text);

        // Estrai stipendio base
        $data['base_salary'] = $this->extractBaseSalary($text);

        // Estrai bonus/premi
        $data['bonus'] = $this->extractBonus($text);

        // Estrai straordinari
        $overtimeData = $this->extractOvertime($text);
        $data['overtime_hours'] = $overtimeData['hours'];
        $data['overtime_rate'] = $overtimeData['rate'];

        // Estrai tasse e contributi
        $data['tax_amount'] = $this->extractTaxes($text);

        // Estrai detrazioni
        $data['deductions'] = $this->extractDeductions($text);

        // Estrai totali
        $totals = $this->extractTotals($text);
        $data['gross_salary'] = $totals['gross'];
        $data['net_salary'] = $totals['net'];

        // Se non troviamo nulla, proviamo con pattern più generici
        if (empty(array_filter($data, function ($value) {
            return $value !== null && $value !== 0;
        }))) {
            $data = $this->extractGenericAmounts($text);
        }

        return array_filter($data, function ($value) {
            return $value !== null && $value !== 0;
        });
    }

    private function normalizeText(string $text): string
    {
        // Rimuovi caratteri speciali e normalizza spazi
        $text = preg_replace('/\s+/', ' ', $text);
        $text = str_replace([',', '.'], ['.', '.'], $text);
        return trim($text);
    }

    private function extractBaseSalary(string $text): ?float
    {
        // Pattern specifici per buste paga italiane
        $patterns = [
            '/PAGA\s*BASE\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/\*\*Z00001Retribuzione[^€]*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/stipendio\s*base[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/retribuzione\s*base[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/paga\s*base[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/salario[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractBonus(string $text): ?float
    {
        $patterns = [
            '/bonus[:\s]*€?\s*(\d+[.,]\d{2})/i',
            '/premio[:\s]*€?\s*(\d+[.,]\d{2})/i',
            '/incentivo[:\s]*€?\s*(\d+[.,]\d{2})/i',
            '/gratifica[:\s]*€?\s*(\d+[.,]\d{2})/i',
            '/contingenza[:\s]*(\d+[.,]\d{2})/i',
            '/CONTING\.[:\s]*(\d+[.,]\d{2})/i',
            '/3\'ELEMEN\.[:\s]*(\d+[.,]\d{2})/i',
            '/\*\*Z5000013ma\s+Mensilita[^\d]+(\d+[.,]\d{2})/i',
            '/\*\*Z5002214ma\s+Mensilita[^\d]+(\d+[.,]\d{2})/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractOvertime(string $text): array
    {
        $overtimeHours = null;
        $overtimeRate = null;
        $overtimeAmount = null;

        // Pattern per ore straordinari italiane
        $hourPatterns = [
            '/straordinari[:\s]*(\d+[.,]\d{1,5})\s*ore/i',
            '/ore\s*straordinari[:\s]*(\d+[.,]\d{1,5})/i',
            '/\*\*Z40015Straordinario.*?(\d+[.,]\d{5})ORE/i',
        ];

        // Pattern per importo straordinari
        $amountPatterns = [
            '/\*\*Z40015Straordinario[^€]*(\d+[.,]\d{2})/i',
            '/straordinario.*?(\d+[.,]\d{2})€?/i',
        ];

        // Pattern per tariffa straordinari
        $ratePatterns = [
            '/straordinari.*?€?\s*(\d+[.,]\d{2})\s*\/\s*ora/i',
            '/€?\s*(\d+[.,]\d{2})\s*\/\s*ora.*straordinari/i',
            '/\*\*Z40015Straordinario.*?(\d+[.,]\d{5})\s+(\d+[.,]\d{5})ORE/i',
        ];

        $overtimeHours = $this->extractAmountByPatterns($text, $hourPatterns);
        $overtimeAmount = $this->extractAmountByPatterns($text, $amountPatterns);
        $overtimeRate = $this->extractAmountByPatterns($text, $ratePatterns);

        return [
            'hours' => $overtimeHours,
            'rate' => $overtimeRate,
            'amount' => $overtimeAmount,
        ];
    }

    private function extractTaxes(string $text): ?float
    {
        $patterns = [
            '/F03020Ritenute\s*IRPEF\s*(\d{1,4}[.,]\d{2})/i',
            '/F06020Ritenute\s*IRPEF\s*Tass\.aut\.\s*(\d{1,4}[.,]\d{2})/i',
            '/IRPEF\s*pagata\s*(\d{1,4}[.,]\d{2})/i',
            '/F02010IRPEF\s*lorda\s*(\d{1,4}[.,]\d{2})/i',
            '/irpef[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
            '/tasse[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
            '/imposte[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
            '/ritenute[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractDeductions(string $text): ?float
    {
        $patterns = [
            '/\*Z00000Contributo\s*IVS[^€]*(\d{1,4}[.,]\d{2})/i',
            '/\*Z00055FIS[^€]*(\d{1,4}[.,]\d{2})/i',
            '/\*Z00087Contributo\s*CIGS[^€]*(\d{1,4}[.,]\d{2})/i',
            '/001853Contributo\s*Ente[^€]*(\d{1,4}[.,]\d{2})/i',
            '/\*Z31000Contributo\s*Fondo[^€]*(\d{1,4}[.,]\d{2})/i',
            '/TOTALEsTRATTENUTE\s*(\d{1,4}[.,]\d{2})/i',
            '/contributi[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
            '/detrazioni[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
            '/trattenute[:\s]*€?\s*(\d{1,4}[.,]\d{2})/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractTotals(string $text): array
    {
        $grossPatterns = [
            '/TOTALEsCOMPETENZE\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})\s*TOTALEsCOMPETENZE/i',
            '/totale\s*lordo[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/imponibile[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/lordo[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
        ];

        $netPatterns = [
            '/(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})€\s*$/m',
            '/TOTALEsNETTOsDELsMESE\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})€\s*REVOLUT/i',
            '/totale\s*netto[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/netto\s*in\s*busta[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
            '/da\s*pagare[:\s]*€?\s*(\d{1,3}\.\d{3},\d{2}|\d{1,4}[.,]\d{2})/i',
        ];

        return [
            'gross' => $this->extractAmountByPatterns($text, $grossPatterns),
            'net' => $this->extractAmountByPatterns($text, $netPatterns),
        ];
    }

    private function extractAmountByPatterns(string $text, array $patterns): ?float
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = $matches[1];

                // Gestisce il formato italiano 1.653,69 (punto per migliaia, virgola per decimali)
                if (preg_match('/^(\d{1,3})\.(\d{3}),(\d{2})$/', $amount)) {
                    // Formato: 1.653,69 -> 1653.69
                    $amount = str_replace('.', '', $amount);
                    $amount = str_replace(',', '.', $amount);
                } elseif (preg_match('/^(\d+),(\d{2})$/', $amount)) {
                    // Formato: 532,54 -> 532.54
                    $amount = str_replace(',', '.', $amount);
                } elseif (preg_match('/^(\d+)\.(\d{2})$/', $amount)) {
                    // Formato: 1653.69 (già corretto)
                    // Non fare nulla
                }

                return (float) $amount;
            }
        }
        return null;
    }

    private function extractPeriod(string $text, string $fileName): array
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Prova a estrarre da patterns nel testo delle buste paga italiane
        $patterns = [
            '/(\d{1,2})\/(\d{4})/i', // MM/YYYY
            '/(\d{1,2})-(\d{4})/i', // MM-YYYY
            '/(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre)\s*(\d{4})/i',
            '/PERIODOsDIsRETRIBUZIONE[^a-zA-Z]*(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre)\s*(\d{4})/i',
        ];

        $monthNames = [
            'gennaio' => 1,
            'febbraio' => 2,
            'marzo' => 3,
            'aprile' => 4,
            'maggio' => 5,
            'giugno' => 6,
            'luglio' => 7,
            'agosto' => 8,
            'settembre' => 9,
            'ottobre' => 10,
            'novembre' => 11,
            'dicembre' => 12
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($monthNames[strtolower($matches[1])])) {
                    return [
                        'month' => $monthNames[strtolower($matches[1])],
                        'year' => (int) $matches[2]
                    ];
                } else {
                    return [
                        'month' => (int) $matches[1],
                        'year' => (int) $matches[2]
                    ];
                }
            }
        }

        // Prova a estrarre dal nome file
        if (preg_match('/(\d{1,2})[-_](\d{4})/', $fileName, $matches)) {
            return [
                'month' => (int) $matches[1],
                'year' => (int) $matches[2]
            ];
        }

        // Default al mese corrente
        return [
            'month' => $currentMonth,
            'year' => $currentYear
        ];
    }

    private function createSalaryFromPaySlip(PaySlip $paySlip, array $extractedData): void
    {
        // Verifica se esiste già un salary per questo mese/anno
        $existing = Salary::where('user_id', $paySlip->user_id)
            ->where('month', $extractedData['month'])
            ->where('year', $extractedData['year'])
            ->first();

        if ($existing) {
            Log::info('Salary già esistente per il periodo', [
                'user_id' => $paySlip->user_id,
                'month' => $extractedData['month'],
                'year' => $extractedData['year']
            ]);
            return;
        }

        $salaryData = [
            'base_salary' => $extractedData['base_salary'] ?? 0,
            'bonus' => $extractedData['bonus'] ?? 0,
            'overtime_hours' => $extractedData['overtime_hours'] ?? 0,
            'overtime_rate' => $extractedData['overtime_rate'] ?? 0,
            'deductions' => $extractedData['deductions'] ?? 0,
            'tax_amount' => $extractedData['tax_amount'] ?? 0,
            'month' => $extractedData['month'],
            'year' => $extractedData['year'],
            'notes' => 'Generato automaticamente da busta paga',
            'pay_slip_id' => $paySlip->id,
            'auto_generated' => true,
        ];

        // Se abbiamo i totali, usali, altrimenti calcola
        if (isset($extractedData['gross_salary'])) {
            $salaryData['gross_salary'] = $extractedData['gross_salary'];
        }
        if (isset($extractedData['net_salary'])) {
            $salaryData['net_salary'] = $extractedData['net_salary'];
        }

        $this->salaryService->createSalary(
            User::find($paySlip->user_id),
            $salaryData
        );
    }

    private function extractGenericAmounts(string $text): array
    {
        // Pattern generici per trovare almeno alcuni valori monetari
        $amounts = [];

        // Cerca tutti i numeri con formato euro
        preg_match_all('/€\s*(\d{1,5}(?:[.,]\d{2})?)/i', $text, $euroMatches);

        // Se non troviamo euro, cerca numeri con formato monetario
        if (empty($euroMatches[1])) {
            preg_match_all('/\b(\d{1,5}[.,]\d{2})\b/', $text, $numberMatches);
            $matches = $numberMatches;
        } else {
            $matches = $euroMatches;
        }

        if (!empty($matches[1])) {
            $values = array_map(function ($amount) {
                return (float) str_replace(',', '.', $amount);
            }, $matches[1]);

            // Ordina i valori per importanza (dal più grande al più piccolo)
            rsort($values);

            // Il valore più alto potrebbe essere lordo, il secondo netto
            if (count($values) >= 2) {
                return [
                    'gross_salary' => $values[0],
                    'net_salary' => $values[1],
                    'base_salary' => $values[1], // Usa il netto come base se non troviamo altro
                ];
            } elseif (count($values) >= 1) {
                return [
                    'net_salary' => $values[0],
                    'base_salary' => $values[0],
                ];
            }
        }

        return [];
    }
}
