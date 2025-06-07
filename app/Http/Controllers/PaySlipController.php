<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaySlip;
use App\Services\PaySlipParserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class PaySlipController extends Controller
{
    public function __construct(
        private readonly PaySlipParserService $parserService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $paySlips = PaySlip::forUser($user->id)
            ->with('salary')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('PaySlip/Index', [
            'paySlips' => $paySlips,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
            ],
        ], [
            'file.required' => 'Seleziona un file da caricare.',
            'file.mimes' => 'Il file deve essere in formato PDF.',
            'file.max' => 'Il file non può essere più grande di 10MB.',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        // Genera un nome file unico
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('pay-slips', $fileName, 'public');

        // Crea il record PaySlip
        $paySlip = PaySlip::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        // Avvia il processing in background
        $this->processPaySlip($paySlip);

        return response()->json([
            'message' => 'Busta paga caricata con successo. Elaborazione in corso...',
            'paySlip' => $paySlip,
        ], 201);
    }

    public function process(PaySlip $paySlip): JsonResponse
    {
        $this->authorize('view', $paySlip);

        if ($paySlip->processed) {
            return response()->json([
                'message' => 'Busta paga già elaborata.',
                'paySlip' => $paySlip->load('salary'),
            ]);
        }

        $success = $this->parserService->parsePaySlip($paySlip);

        if ($success) {
            return response()->json([
                'message' => 'Busta paga elaborata con successo.',
                'paySlip' => $paySlip->fresh()->load('salary'),
            ]);
        }

        return response()->json([
            'message' => 'Errore nell\'elaborazione della busta paga.',
            'error' => $paySlip->processing_error,
        ], 422);
    }

    public function show(PaySlip $paySlip): Response
    {
        $this->authorize('view', $paySlip);

        return Inertia::render('PaySlip/Show', [
            'paySlip' => $paySlip->load('salary'),
        ]);
    }

    public function destroy(PaySlip $paySlip): JsonResponse
    {
        $this->authorize('delete', $paySlip);

        // Elimina il file fisico
        if (Storage::disk('public')->exists($paySlip->file_path)) {
            Storage::disk('public')->delete($paySlip->file_path);
        }

        // Se esiste un salary collegato e auto-generato, chiedi conferma o elimina
        if ($paySlip->salary && $paySlip->salary->auto_generated) {
            $paySlip->salary->delete();
        }

        $paySlip->delete();

        return response()->json([
            'message' => 'Busta paga eliminata con successo.',
        ]);
    }

    public function download(PaySlip $paySlip)
    {
        $this->authorize('view', $paySlip);

        $filePath = storage_path('app/public/' . $paySlip->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File non trovato');
        }

        return response()->download($filePath, $paySlip->file_name);
    }

    private function processPaySlip(PaySlip $paySlip): void
    {
        try {
            $this->parserService->parsePaySlip($paySlip);
        } catch (\Exception $e) {
            // Log dell'errore già gestito nel service
        }
    }
}
