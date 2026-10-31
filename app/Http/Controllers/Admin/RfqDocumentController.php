<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Procurement\RfqDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RfqDocumentController extends Controller
{
    public function index(): View
    {
        $documents = RfqDocument::query()->where('is_active', true)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        return view('admin.rfq-documents.index', compact('documents'));
    }

    public function archive(): View
    {
        $documents = RfqDocument::query()->where('is_active', false)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        return view('admin.rfq-documents.archive', compact('documents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        RfqDocument::create([
            'sequence' => (int) RfqDocument::query()->max('sequence') + 1,
            'title' => trim($validated['title']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.rfq-documents.index')->with('success', 'RFQ document added.');
    }

    public function update(Request $request, RfqDocument $rfqDocument): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rfqDocument->update([
            'title' => trim($validated['title']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.rfq-documents.index')->with('success', 'RFQ document updated.');
    }

    public function toggle(RfqDocument $rfqDocument): RedirectResponse
    {
        DB::transaction(function () use ($rfqDocument): void {
            $wasActive = (bool) $rfqDocument->is_active;
            if ($wasActive) {
                $rfqDocument->update(['is_active' => false]);
                RfqDocument::query()->where('is_active', true)
                    ->orderBy('sequence')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->values()
                    ->each(fn (RfqDocument $document, int $index) => $document->update(['sequence' => $index + 1]));
                return;
            }

            $rfqDocument->update([
                'is_active' => true,
                'sequence' => (int) RfqDocument::query()->where('is_active', true)->max('sequence') + 1,
            ]);
        });

        return redirect()
            ->route($rfqDocument->is_active ? 'admin.rfq-documents.index' : 'admin.rfq-documents.archive')
            ->with('success', $rfqDocument->is_active ? 'RFQ document restored.' : 'RFQ document archived.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);
        $ids = array_map('intval', $validated['order']);

        DB::transaction(function () use ($ids): void {
            $activeIds = RfqDocument::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $submittedIds = $ids;
            sort($submittedIds);
            $expectedIds = $activeIds;
            sort($expectedIds);
            abort_unless($submittedIds === $expectedIds, 422, 'The RFQ document order is out of date. Reload and try again.');

            foreach ($ids as $position => $id) {
                RfqDocument::query()->whereKey($id)->update(['sequence' => $position + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(RfqDocument $rfqDocument): RedirectResponse
    {
        if ($rfqDocument->procurements()->exists()) {
            return redirect()
                ->route('admin.rfq-documents.index')
                ->with('error', 'This RFQ document is assigned to a procurement and cannot be deleted. Deactivate it instead.');
        }

        $rfqDocument->delete();

        return redirect()->route('admin.rfq-documents.index')->with('success', 'RFQ document deleted.');
    }
}
