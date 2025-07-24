<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMissedContacts;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProcessMissedContactsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:process-missed 
                            {--since= : Process contacts since this time (e.g. "2025-01-24 10:00:00" or "1 hour ago")}
                            {--notify : Send email notifications}
                            {--mark-processed : Mark contacts as processed by job}
                            {--dry-run : Show what would be processed without actually doing it}
                            {--stats : Show only statistics without processing}
                            {--reset-last-time : Reset the last process time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process missed contacts received during app hibernation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('reset-last-time')) {
            Cache::forget('last_contact_process_time');
            $this->info('✅ Last process time has been reset');
            return 0;
        }

        if ($this->option('stats')) {
            $this->showStatistics();
            return 0;
        }

        $this->info('🔍 Processing missed contacts...');
        $this->newLine();

        try {
            // Determine the time since when to process
            $sinceTime = $this->getSinceTime();
            $this->info("📅 Processing contacts since: {$sinceTime->format('Y-m-d H:i:s')}");

            // Get the contacts to process
            $contacts = $this->getContactsToProcess($sinceTime);
            
            if ($contacts->isEmpty()) {
                $this->info('✅ No missed contacts found');
                return 0;
            }

            $this->info("📋 Found {$contacts->count()} contacts to process");
            $this->newLine();

            if ($this->option('dry-run')) {
                $this->showDryRunResults($contacts);
                return 0;
            }

            // Confirm processing
            if (!$this->confirmProcessing($contacts->count())) {
                $this->warn('❌ Processing cancelled');
                return 1;
            }

            // Execute the job
            $this->processContacts($sinceTime);

            $this->newLine();
            $this->info('✅ Missed contacts processed successfully!');
            
            if ($this->option('notify')) {
                $this->info('📧 Notifications have been sent');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error processing missed contacts: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Get the time since when to process contacts
     */
    private function getSinceTime(): Carbon
    {
        $since = $this->option('since');
        
        if ($since) {
            try {
                // Try to parse as exact datetime first
                return Carbon::parse($since);
            } catch (\Exception $e) {
                // If that fails, try relative time
                try {
                    return Carbon::parse($since);
                } catch (\Exception $e) {
                    $this->error("❌ Invalid time format: {$since}");
                    $this->info("💡 Examples: '2025-01-24 10:00:00', '1 hour ago', 'yesterday'");
                    exit(1);
                }
            }
        }

        // Use last process time from cache
        $lastProcessTime = Cache::get('last_contact_process_time');
        
        if ($lastProcessTime) {
            return Carbon::parse($lastProcessTime);
        }

        // Default to 1 hour ago
        return now()->subHour();
    }

    /**
     * Get contacts to process
     */
    private function getContactsToProcess(Carbon $sinceTime)
    {
        return Contact::where('created_at', '>=', $sinceTime)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Show dry run results
     */
    private function showDryRunResults($contacts): void
    {
        $this->warn('🧪 DRY RUN MODE - No changes will be made');
        $this->newLine();

        $unreadCount = $contacts->where('read', false)->count();
        $originsCount = $contacts->groupBy('origin')->map->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total contacts', $contacts->count()],
                ['Unread contacts', $unreadCount],
                ['Unique origins', $originsCount->count()],
            ]
        );

        $this->newLine();
        $this->info('📍 Top origins:');
        
        $topOrigins = $originsCount->sortDesc()->take(5);
        foreach ($topOrigins as $origin => $count) {
            $this->line("  • {$origin}: {$count}");
        }

        $this->newLine();
        $this->info('📝 Recent contacts:');
        $recentContacts = $contacts->take(5);
        
        foreach ($recentContacts as $contact) {
            $urgentBadge = $this->isUrgentContact($contact) ? '⚠️ ' : '';
            $readBadge = $contact->read ? '✓' : '○';
            $this->line("  {$readBadge} {$urgentBadge}{$contact->name} ({$contact->email})");
            $this->line("    📧 " . Str::limit($contact->message, 80));
            $this->line("    🕐 {$contact->created_at->format('Y-m-d H:i:s')}");
            $this->newLine();
        }

        $this->info('🏃‍♂️ Run without --dry-run to actually process these contacts');
    }

    /**
     * Check if contact is urgent
     */
    private function isUrgentContact($contact): bool
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
     * Confirm processing with user
     */
    private function confirmProcessing(int $count): bool
    {
        $actions = [];
        
        if ($this->option('notify')) {
            $actions[] = 'send notifications';
        }
        
        if ($this->option('mark-processed')) {
            $actions[] = 'mark as processed';
        }
        
        $actionsText = !empty($actions) ? ' and ' . implode(', ', $actions) : '';
        
        return $this->confirm("Process {$count} contacts{$actionsText}?", true);
    }

    /**
     * Process the contacts using the job
     */
    private function processContacts(Carbon $sinceTime): void
    {
        $sendNotifications = $this->option('notify');
        $markAsProcessed = $this->option('mark-processed');

        $this->info('🚀 Dispatching ProcessMissedContacts job...');
        
        $job = new ProcessMissedContacts($sinceTime, $sendNotifications, $markAsProcessed);
        
        // Run synchronously for immediate feedback
        $job->handle();
        
        $this->info('✅ Job completed');
    }

    /**
     * Show processing statistics
     */
    private function showStatistics(): void
    {
        $this->info('📊 Missed Contacts Processing Statistics');
        $this->newLine();

        $stats = ProcessMissedContacts::getProcessingStats();
        
        if (empty($stats) || $stats['total'] === 0) {
            $this->warn('📭 No processing statistics available');
            $this->info('💡 Run the command with contacts to process first');
            return;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total processed', $stats['total']],
                ['Unread', $stats['unread']],
                ['Urgent', $stats['urgent']],
                ['Time range', $stats['time_range']['from'] ?? 'N/A'],
                ['To', $stats['time_range']['to'] ?? 'N/A'],
            ]
        );

        if (!empty($stats['origins'])) {
            $this->newLine();
            $this->info('🌐 Top origins:');
            foreach ($stats['origins'] as $origin => $count) {
                $this->line("  • {$origin}: {$count}");
            }
        }

        $lastProcessTime = Cache::get('last_contact_process_time');
        if ($lastProcessTime) {
            $this->newLine();
            $this->info("🕐 Last process time: " . Carbon::parse($lastProcessTime)->format('Y-m-d H:i:s'));
        }
    }
}
