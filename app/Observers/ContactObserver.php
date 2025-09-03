<?php

namespace App\Observers;

use App\Mail\NewContactNotification;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactObserver
{
    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        try {
            Mail::to('francescosisti61@gmail.com')->send(new NewContactNotification($contact));
        } catch (\Throwable $e) {
            Log::error('Contact notification email failed', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
