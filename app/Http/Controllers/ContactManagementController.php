<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactManagementController extends Controller
{
    /**
     * Display contacts index page
     */
    public function index(Request $request): Response
    {
        $query = Contact::query();

        // Filters
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('origin')) {
            $query->fromOrigin($request->origin);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting with whitelist
        $allowedSorts = ['created_at', 'name', 'email', 'read'];
        $sortBy = in_array($request->get('sort_by', 'created_at'), $allowedSorts, true)
            ? $request->get('sort_by', 'created_at')
            : 'created_at';
        $sortOrder = $request->get('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination size (per_page)
        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $contacts = $query->paginate($perPage)->withQueryString();

        // Statistics
        $stats = [
            'total' => Contact::count(),
            'unread' => Contact::unread()->count(),
            'today' => Contact::whereDate('created_at', today())->count(),
            'this_week' => Contact::where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        // Origins for filter
        $origins = Contact::selectRaw('origin, count(*) as count')
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->groupBy('origin')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Contact/Index', [
            'contacts' => $contacts,
            'stats' => $stats,
            'origins' => $origins,
            'filters' => $request->only(['status', 'search', 'origin', 'date_from', 'date_to', 'sort_by', 'sort_order', 'per_page']),
        ]);
    }

    /**
     * Display single contact
     */
    public function show(Contact $contact): Response
    {
        // Mark as read when viewed
        if (!$contact->read) {
            $contact->markAsRead();
        }

        return Inertia::render('Contact/Show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Mark contact as read/unread
     */
    public function toggleRead(Contact $contact)
    {
        $newStatus = !$contact->read;
        $contact->update(['read' => $newStatus]);

        return back()->with('success', $newStatus ? 'Contatto marcato come letto' : 'Contatto marcato come non letto');
    }

    /**
     * Mark multiple contacts as read
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $updated = Contact::whereIn('id', $request->contact_ids)
            ->update(['read' => true]);

        return back()->with('success', "Marcati {$updated} contatti come letti");
    }

    /**
     * Delete contact
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        // If coming from show page, redirect to index
        if (str_contains(request()->header('referer', ''), '/contacts/' . $contact->id)) {
            return redirect()->route('contacts.index')->with('success', 'Contatto eliminato con successo');
        }

        // Otherwise, go back to previous page (index with filters)
        return back()->with('success', 'Contatto eliminato con successo');
    }

    /**
     * Delete multiple contacts
     */
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $deleted = Contact::whereIn('id', $request->contact_ids)->delete();

        return back()->with('success', "Eliminati {$deleted} contatti");
    }

    /**
     * Export contacts to CSV
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = Contact::query();

        // Apply same filters as index
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('origin')) {
            $query->fromOrigin($request->origin);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'contacts_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, [
                'ID',
                'Nome',
                'Email',
                'Oggetto',
                'Messaggio',
                'Telefono',
                'Azienda',
                'Origine',
                'IP Address',
                'User Agent',
                'Stato',
                'Data Creazione',
                'Dati Extra'
            ]);

            // Data rows
            $query->chunk(1000, function ($contacts) use ($handle) {
                foreach ($contacts as $contact) {
                    fputcsv($handle, [
                        $contact->id,
                        $contact->name,
                        $contact->email,
                        $contact->subject,
                        $contact->message,
                        $contact->phone,
                        $contact->company,
                        $contact->origin,
                        $contact->ip_address,
                        $contact->user_agent,
                        $contact->read ? 'Letto' : 'Non letto',
                        $contact->created_at->format('d/m/Y H:i:s'),
                        $contact->extra_data ? json_encode($contact->extra_data) : ''
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get dashboard stats for contacts
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Contact::count(),
            'unread' => Contact::unread()->count(),
            'today' => Contact::whereDate('created_at', today())->count(),
            'yesterday' => Contact::whereDate('created_at', now()->subDay())->count(),
            'this_week' => Contact::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => Contact::where('created_at', '>=', now()->startOfMonth())->count(),
            'recent' => Contact::recent(7)->count(),
        ];

        // Trending origins
        $topOrigins = Contact::selectRaw('origin, count(*) as count')
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('origin')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        // Daily counts for last 30 days
        $dailyCounts = Contact::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'stats' => $stats,
            'top_origins' => $topOrigins,
            'daily_counts' => $dailyCounts,
        ]);
    }
}
