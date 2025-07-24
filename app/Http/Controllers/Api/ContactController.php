<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    /**
     * Store a new contact from external form
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        // Rate limiting per IP
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'contact-form:' . $clientIp;
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Troppi tentativi. Riprova tra ' . $seconds . ' secondi.',
            ], 429);
        }
        
        RateLimiter::hit($key, 300); // 5 minutes

        try {
            // Create contact record
            $contactData = $request->validated();
            $contactData['ip_address'] = $clientIp;
            $contactData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Set origin from referer if not provided
            if (empty($contactData['origin'])) {
                $contactData['origin'] = $_SERVER['HTTP_REFERER'] ?? 'unknown';
            }

            $contact = Contact::create($contactData);

            // Log the contact submission
            Log::info('New contact form submission', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'origin' => $contact->origin,
                'ip' => $contact->ip_address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Messaggio inviato con successo. Ti risponderemo al più presto!',
                'contact_id' => $contact->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating contact', [
                'error' => $e->getMessage(),
                'ip' => $clientIp,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore interno del server. Riprova più tardi.',
            ], 500);
        }
    }

    /**
     * Get contact status (for tracking)
     */
    public function show(string $id): JsonResponse
    {
        try {
            $contact = Contact::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'contact' => [
                    'id' => $contact->id,
                    'status' => $contact->read ? 'read' : 'unread',
                    'created_at' => $contact->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Contatto non trovato',
            ], 404);
        }
    }

    /**
     * Get API status and configuration
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'api_version' => '1.0',
            'status' => 'active',
            'endpoints' => [
                'submit' => route('api.contacts.store'),
                'status' => route('api.contacts.show', ['contact' => '{id}']),
            ],
            'rate_limits' => [
                'requests_per_5_minutes' => 5,
                'max_message_length' => 5000,
            ],
        ]);
    }
}
