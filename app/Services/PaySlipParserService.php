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

            $extractedData = $this->extractDataFromText($text);

            if (empty($extractedData)) {
                $paySlip->markAsError('Impossibile estrarre dati dal PDF');
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
        // Pattern comuni per stipendio base
        $patterns = [
            '/stipendio\s*base[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/retribuzione\s*base[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/paga\s*base[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/salario[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractBonus(string $text): ?float
    {
        $patterns = [
            '/bonus[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/premio[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/incentivo[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/gratifica[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractOvertime(string $text): array
    {
        $overtimeHours = null;
        $overtimeRate = null;

        // Pattern per ore straordinari
        $hourPatterns = [
            '/straordinari[:\s]*(\d+(?:\.\d{1,2})?)\s*ore/i',
            '/ore\s*straordinari[:\s]*(\d+(?:\.\d{1,2})?)/i',
        ];

        // Pattern per tariffa straordinari
        $ratePatterns = [
            '/straordinari.*?€?\s*(\d+(?:\.\d{2})?)\s*\/\s*ora/i',
            '/€?\s*(\d+(?:\.\d{2})?)\s*\/\s*ora.*straordinari/i',
        ];

        $overtimeHours = $this->extractAmountByPatterns($text, $hourPatterns);
        $overtimeRate = $this->extractAmountByPatterns($text, $ratePatterns);

        return [
            'hours' => $overtimeHours,
            'rate' => $overtimeRate,
        ];
    }

    private function extractTaxes(string $text): ?float
    {
        $patterns = [
            '/irpef[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/tasse[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/imposte[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/ritenute[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractDeductions(string $text): ?float
    {
        $patterns = [
            '/contributi[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/detrazioni[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/trattenute[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
        ];

        return $this->extractAmountByPatterns($text, $patterns);
    }

    private function extractTotals(string $text): array
    {
        $grossPatterns = [
            '/totale\s*lordo[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/imponibile[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/lordo[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
        ];

        $netPatterns = [
            '/totale\s*netto[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/netto\s*in\s*busta[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/da\s*pagare[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
            '/netto[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
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
                return (float) str_replace('.', '', $matches[1]);
            }
        }
        return null;
    }

    private function extractPeriod(string $text, string $fileName): array
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Prova a estrarre da patterns nel testo
        $patterns = [
            '/(\d{1,2})\/(\d{4})/i', // MM/YYYY
            '/(\d{1,2})-(\d{4})/i', // MM-YYYY
            '/(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre)\s*(\d{4})/i',
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
}
