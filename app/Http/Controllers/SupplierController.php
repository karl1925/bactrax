<?php

namespace App\Http\Controllers;

use App\Models\Procurement\Supplier;
use App\Models\Procurement\SupplierDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->withCount('bids')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('business_name', 'like', '%'.trim($request->search).'%')->orWhere('tin', 'like', '%'.trim($request->search).'%')->orWhere('contact_person', 'like', '%'.trim($request->search).'%')))
            ->orderBy('business_name')->paginate(20)->withQueryString();

        $archivedCount = Supplier::query()->where('is_active', false)->count();

        return view('suppliers.index', compact('suppliers', 'archivedCount'));
    }

    public function archivedIndex(Request $request): View
    {
        $suppliers = Supplier::query()
            ->where('is_active', false)
            ->withCount('bids')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('business_name', 'like', '%'.trim($request->search).'%')->orWhere('tin', 'like', '%'.trim($request->search).'%')->orWhere('contact_person', 'like', '%'.trim($request->search).'%')))
            ->orderBy('business_name')->paginate(20)->withQueryString();

        return view('suppliers.archive', compact('suppliers'));
    }

    public function toggleActive(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return back()->with('success', $supplier->is_active
            ? 'Supplier profile restored to the active directory.'
            : 'Supplier profile moved to the archive.');
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier profile created.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['latestDocuments.uploader', 'bids.procurement']);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier profile updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->bids()->exists()) {
            return back()->with('error', 'This supplier has bid history and cannot be deleted. Deactivate the profile instead.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier profile deleted.');
    }

    public function uploadDocument(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'document_name' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:128'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);
        $file = $request->file('file');
        $path = $file->store("suppliers/{$supplier->id}", 'local');

        try {
            $supplier->documents()->create([
                ...collect($data)->except('file')->all(),
                'disk' => 'local', 'file_path' => $path,
                'original_filename' => basename($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType() ?: 'application/pdf',
                'file_size' => $file->getSize(), 'uploaded_by' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Supplier document uploaded.');
    }

    public function downloadDocument(Supplier $supplier, SupplierDocument $document)
    {
        abort_unless($document->supplier_id === $supplier->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_filename);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'business_address' => ['nullable', 'string', 'max:5000'],
            'tin' => ['nullable', 'string', 'max:64'],
            'philgeps_registration_number' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
