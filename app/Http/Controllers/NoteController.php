<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notes = Note::query()
            ->forUser($user->id)
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Note/Index', [
            'notes' => $notes,
        ]);
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $note = Note::create([
            ...$data,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Nota creata con successo',
            'note' => $note,
        ], 201);
    }

    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        return Inertia::render('Note/Show', [
            'note' => $note,
        ]);
    }

    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return response()->json([
            'message' => 'Nota aggiornata con successo',
            'note' => $note->fresh(),
        ]);
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);
        $note->delete();

        return back()->with('success', 'Nota eliminata');
    }

    public function togglePin(Note $note): JsonResponse
    {
        $this->authorize('update', $note);
        $note->update(['pinned' => ! $note->pinned]);

        return response()->json([
            'message' => $note->pinned ? 'Nota fissata in alto' : 'Nota rimossa dai preferiti',
            'note' => $note->fresh(),
        ]);
    }
}