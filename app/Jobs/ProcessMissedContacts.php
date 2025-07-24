<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProcessMissedContacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Carbon $lastCheckTime;
    private bool $sendNotifications;
    private bool $markAsProcessed;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ?Carbon $lastCheckTime = null,
        bool $sendNotifications = true,
        bool $markAsProcessed = false
    ) {
        $this->lastCheckTime = $lastCheckTime ?? $this->getLastProcessTime();
        $this->sendNotifications = $sendNotifications;
        $this->markAsProcessed = $markAsProcessed;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting ProcessMissedContacts job', [
            'last_check_time' => $this->lastCheckTime->toISOString(),
            'send_notifications' => $this->sendNotifications,
            'mark_as_processed' => $this->markAsProcessed,
        ]);

        try {
            // Get contacts received since last check
            $missedContacts = $this->getMissedContacts();
            
            if ($missedContacts->isEmpty()) {
                Log::info('No missed contacts found');
                $this->updateLastProcessTime();
                return;
            }

            Log::info("Found {$missedContacts->count()} missed contacts");

            // Process the contacts
            $processedData = $this->processContacts($missedContacts);

            // Send notification if enabled
            if ($this->sendNotifications && $processedData['total'] > 0) {
                $this->sendMissedContactsNotification($processedData);
            }

            // Mark contacts as processed if enabled
            if ($this->markAsProcessed) {
                $this->markContactsAsProcessed($missedContacts);
            }

            // Store statistics
            $this->storeProcessingStats($processedData);

            // Update last process time
            $this->updateLastProcessTime();

            Log::info('ProcessMissedContacts job completed successfully', $processedData);

        } catch (\Exception $e) {
            Log::error('Error in ProcessMissedContacts job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get contacts received since last check
     */
    private function getMissedContacts()
    {
        return Contact::where('created_at', '>=', $this->lastCheckTime)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Process the missed contacts and return statistics
     */
    private function processContacts($contacts): array
    {
        $stats = [
            'total' => $contacts->count(),
            'unread' => 0,
            'urgent' => 0,
            'origins' => [],
            'time_range' => [
                'from' => $this->lastCheckTime->toISOString(),
                'to' => now()->toISOString(),
            ],
            'contacts' => [],
        ];

        $originCounts = [];

        foreach ($contacts as $contact) {
            // Count unread
            if (!$contact->read) {
                $stats['unread']++;
            }

            // Check for urgent keywords
            if ($this->isUrgentContact($contact)) {
                $stats['urgent']++;
                $contact->urgent = true;
            }

            // Count origins
            $origin = $contact->origin ?: 'unknown';
            $originCounts[$origin] = ($originCounts[$origin] ?? 0) + 1;

            // Add to contacts list for notification
            $stats['contacts'][] = [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'subject' => $contact->subject,
                'message' => substr($contact->message, 0, 100) . (strlen($contact->message) > 100 ? '...' : ''),
                'origin' => $contact->origin,
                'created_at' => $contact->created_at->toISOString(),
                'urgent' => $contact->urgent ?? false,
            ];
        }

        // Sort origins by count
        arsort($originCounts);
        $stats['origins'] = array_slice($originCounts, 0, 5, true);

        return $stats;
    }

    /**
     * Check if a contact should be marked as urgent
     */
    private function isUrgentContact(Contact $contact): bool
    {
        $urgentKeywords = [
            'urgente', 'urgent', 'emergency', 'emergenza',
            'problema grave', 'serious problem',
            'non funziona', 'not working', 'down',
            'aiuto', 'help', 'supporto immediato'
        ];

        $text = strtolower($contact->subject . ' ' . $contact->message);
        
        foreach ($urgentKeywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send notification about missed contacts
     */
    private function sendMissedContactsNotification(array $stats): void
    {
        try {
            // Get admin users (you might want to create a specific notification preference)
            $adminUsers = User::where('email', 'like', '%admin%')
                ->orWhere('email', 'like', '%manager%')
                ->orWhere('id', 1) // First user is usually admin
                ->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found for notifications');
                return;
            }

            $subject = $this->buildNotificationSubject($stats);
            $body = $this->buildNotificationBody($stats);

            foreach ($adminUsers as $user) {
                try {
                    // In a real app, you'd use a proper Mail class
                    // For now, we'll just log the notification
                    Log::info('Missed contacts notification', [
                        'recipient' => $user->email,
                        'subject' => $subject,
                        'stats' => $stats,
                    ]);

                    // You can uncomment this when you have mail configured
                    /*
                    Mail::raw($body, function ($message) use ($user, $subject) {
                        $message->to($user->email)
                                ->subject($subject);
                    });
                    */

                } catch (\Exception $e) {
                    Log::error('Failed to send notification to user', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending missed contacts notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build notification subject
     */
    private function buildNotificationSubject(array $stats): string
    {
        $urgentText = $stats['urgent'] > 0 ? " ({$stats['urgent']} urgenti)" : '';
        return "🔔 {$stats['total']} nuovi contatti ricevuti{$urgentText}";
    }

    /**
     * Build notification body
     */
    private function buildNotificationBody(array $stats): string
    {
        $body = "Ciao,\n\n";
        $body .= "Durante l'ultima inattività dell'applicazione sono stati ricevuti {$stats['total']} nuovi contatti:\n\n";
        
        $body .= "📊 STATISTICHE:\n";
        $body .= "• Totali: {$stats['total']}\n";
        $body .= "• Non letti: {$stats['unread']}\n";
        
        if ($stats['urgent'] > 0) {
            $body .= "• ⚠️ Urgenti: {$stats['urgent']}\n";
        }
        
        $body .= "\n🌐 ORIGINI PRINCIPALI:\n";
        foreach ($stats['origins'] as $origin => $count) {
            $body .= "• {$origin}: {$count}\n";
        }

        if ($stats['urgent'] > 0) {
            $body .= "\n⚠️ CONTATTI URGENTI:\n";
            foreach ($stats['contacts'] as $contact) {
                if ($contact['urgent']) {
                    $body .= "• {$contact['name']} ({$contact['email']})\n";
                    $body .= "  Oggetto: {$contact['subject']}\n";
                    $body .= "  Messaggio: {$contact['message']}\n\n";
                }
            }
        }

        $body .= "\n📝 ULTIMI 5 CONTATTI:\n";
        $recentContacts = array_slice($stats['contacts'], 0, 5);
        foreach ($recentContacts as $contact) {
            $urgentBadge = $contact['urgent'] ? '⚠️ ' : '';
            $body .= "• {$urgentBadge}{$contact['name']} ({$contact['email']})\n";
            $body .= "  {$contact['message']}\n";
            $body .= "  Ricevuto: " . Carbon::parse($contact['created_at'])->format('d/m/Y H:i') . "\n\n";
        }

        $body .= "Accedi al pannello amministrativo per gestire i contatti:\n";
        $body .= url('/contacts') . "\n\n";
        $body .= "Cordiali saluti,\n";
        $body .= "Sistema Hub Personale";

        return $body;
    }

    /**
     * Mark contacts as processed
     */
    private function markContactsAsProcessed($contacts): void
    {
        // Add a custom field to mark as processed by the job
        Contact::whereIn('id', $contacts->pluck('id'))
            ->update([
                'processed_at' => now(),
                'processed_by_job' => true,
            ]);
    }

    /**
     * Store processing statistics in cache
     */
    private function storeProcessingStats(array $stats): void
    {
        $cacheKey = 'missed_contacts_stats_' . now()->format('Y_m_d_H');
        Cache::put($cacheKey, $stats, now()->addDays(7));
        
        // Store latest stats
        Cache::put('latest_missed_contacts_stats', $stats, now()->addDays(1));
    }

    /**
     * Get the last time contacts were processed
     */
    private function getLastProcessTime(): Carbon
    {
        $lastProcessTime = Cache::get('last_contact_process_time');
        
        if (!$lastProcessTime) {
            // If no last process time, use 1 hour ago as default
            return now()->subHour();
        }

        return Carbon::parse($lastProcessTime);
    }

    /**
     * Update the last process time
     */
    private function updateLastProcessTime(): void
    {
        Cache::put('last_contact_process_time', now()->toISOString(), now()->addDays(30));
    }

    /**
     * Get processing statistics
     */
    public static function getProcessingStats(): array
    {
        return Cache::get('latest_missed_contacts_stats', [
            'total' => 0,
            'unread' => 0,
            'urgent' => 0,
            'origins' => [],
            'contacts' => [],
        ]);
    }
}
