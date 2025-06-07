<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePaySlipRequest;
use App\Models\PaySlip;
use App\Services\PaySlipParserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
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

    public function upload(StorePaySlipRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $file = $request->file('file');

            Log::info('Inizio upload busta paga', [
                'user_id' => $user->id,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            // Genera un nome file unico
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('pay-slips', $fileName, 'public');

            Log::info('File salvato', ['file_path' => $filePath]);

            // Crea il record PaySlip
            $paySlip = PaySlip::create([
                'user_id' => $user->id,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            Log::info('PaySlip creato', ['pay_slip_id' => $paySlip->id]);

            // Avvia il processing in background
            $this->processPaySlip($paySlip);

            return response()->json([
                'message' => 'Busta paga caricata con successo. Elaborazione in corso...',
                'paySlip' => $paySlip,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Errore upload busta paga', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Errore durante l\'upload: ' . $e->getMessage(),
            ], 500);
        }
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

    public function destroy(PaySlip $paySlip): RedirectResponse
    {
        $this->authorize('delete', $paySlip);

        try {
            // Elimina il file fisico
            if (Storage::disk('public')->exists($paySlip->file_path)) {
                Storage::disk('public')->delete($paySlip->file_path);
            }

            // Se esiste un salary collegato e auto-generato, chiedi conferma o elimina
            if ($paySlip->salary && $paySlip->salary->auto_generated) {
                $paySlip->salary->delete();
            }

            $paySlip->delete();

            Log::info('PaySlip eliminato', ['pay_slip_id' => $paySlip->id]);

            return redirect()->back()->with('message', 'Busta paga eliminata con successo.');
        } catch (\Exception $e) {
            Log::error('Errore eliminazione PaySlip', [
                'pay_slip_id' => $paySlip->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors('Errore durante l\'eliminazione della busta paga.');
        }
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
