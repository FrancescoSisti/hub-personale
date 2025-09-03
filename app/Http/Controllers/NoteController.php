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

    public function store(StoreNoteRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $note = Note::create([
            ...$data,
            'user_id' => $user->id,
        ]);

        return redirect()->route('notes.index')->with('success', 'Nota creata con successo');
    }

    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        return Inertia::render('Note/Show', [
            'note' => $note,
        ]);
    }

    public function update(UpdateNoteRequest $request, Note $note)
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return back()->with('success', 'Nota aggiornata con successo');
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);
        $note->delete();

        return back()->with('success', 'Nota eliminata');
    }

    public function togglePin(Note $note)
    {
        $this->authorize('update', $note);
        $note->update(['pinned' => ! $note->pinned]);

        return back()->with('success', $note->pinned ? 'Nota fissata in alto' : 'Nota rimossa dai preferiti');
    }
}
