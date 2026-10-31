<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcurementMode\Form;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\ProcurementFormTemplateRenderer;
use Throwable;

class FormController extends Controller
{
    /**
     * Display a listing of the forms.
     */
    public function index(Request $request)
    {
        $forms = Form::where('status', '!=', 'archived')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')->orWhere('description', 'like', '%' . trim($request->search) . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(15)->withQueryString();
        $publishedCount = Form::where('status', 'published')->count();
        $draftCount = Form::where('status', 'draft')->count();
        $archivedCount = Form::where('status', 'archived')->count();
        return view('admin.forms.index', compact('forms', 'publishedCount', 'draftCount', 'archivedCount'));
    }

    public function archivedIndex(Request $request)
    {
        $forms = Form::where('status', 'archived')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')->orWhere('description', 'like', '%' . trim($request->search) . '%')))
            ->latest()->paginate(15)->withQueryString();
        return view('admin.forms.archive', compact('forms'));
    }

    public function archive(Form $form)
    {
        abort_if(in_array($form->status, ['published', 'archived'], true), 422, 'Published forms must be unpublished before they can be archived.');
        $form->update(['status' => 'archived']);
        return redirect()->route('admin.forms.index')->with('success', 'Form template archived successfully.');
    }

    public function restore(Form $form)
    {
        abort_unless($form->status === 'archived', 422);
        $form->update(['status' => 'draft']);
        return redirect()->route('admin.forms.index')->with('success', 'Form template restored as a draft.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.forms.create');
    }

    /**
     * Store a newly created form in storage.
     */
    public function store(Request $request)
    {
        if (is_string($request->input('code'))) $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:forms,code'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'published', 'unpublished'])],
            'file' => ['required', 'file', 'mimetypes:text/html,text/plain', 'mimes:html,htm', 'max:2048'],
            'page_size' => ['required', 'string', Rule::in(['A4', 'Letter', 'Legal', 'Folio', 'A3', 'A5', 'Custom'])],
            'custom_width' => ['nullable', 'required_if:page_size,Custom', 'numeric', 'min:1', 'max:2000'],
            'custom_height' => ['nullable', 'required_if:page_size,Custom', 'numeric', 'min:1', 'max:2000'],
            'margin_top' => ['required', 'numeric', 'min:0', 'max:500'],
            'margin_left' => ['required', 'numeric', 'min:0', 'max:500'],
            'margin_right' => ['required', 'numeric', 'min:0', 'max:500'],
            'margin_bottom' => ['required', 'numeric', 'min:0', 'max:500'],
        ]);

        $file = $request->file('file');
        $originalFilename = basename($file->getClientOriginalName());
        $storedPath = $file->store('forms', 'private');

        $configuration = [
            'page_size' => $validated['page_size'],
            'custom_width' => $validated['custom_width'] ?? null,
            'custom_height' => $validated['custom_height'] ?? null,
            'margins' => [
                'top' => (float) $validated['margin_top'],
                'right' => (float) $validated['margin_right'],
                'bottom' => (float) $validated['margin_bottom'],
                'left' => (float) $validated['margin_left'],
            ],
        ];

        try {
            DB::transaction(fn () => Form::create([
                'code' => trim($validated['code']),
                'name' => $originalFilename,
                'description' => $validated['description'] ?? null,
                'path' => $storedPath,
                'status' => $validated['status'],
                'configuration' => $configuration,
            ]));
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($storedPath);
            throw $exception;
        }

        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form created successfully.');
    }

    /**
     * Display the specified form metadata.
     */
    public function show(Form $form)
    {
        return view('admin.forms.show', compact('form'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Form $form)
    {
        $sharedHeader = Setting::get('forms_header', '');

        return view('admin.forms.edit', compact('form', 'sharedHeader'));
    }

    public function designer(Form $form)
    {
        return view('admin.forms.designer', compact('form'));
    }

    public function saveDesign(Request $request, Form $form)
    {
        $validated = $request->validate([
            'html' => ['required', 'string'],
        ]);

        if (strlen($validated['html']) > 2_000_000) {
            throw ValidationException::withMessages([
                'html' => 'The designed HTML cannot exceed 2 MB.',
            ]);
        }

        if (! $form->path || ! Storage::disk('private')->exists($form->path)) {
            abort(404, 'Form template file not found.');
        }

        if (! Storage::disk('private')->put($form->path, $validated['html'])) {
            throw new \RuntimeException('The form template could not be saved to private storage.');
        }

        $form->touch();

        return response()->json(['ok' => true]);
    }

    /**
     * Serve the private HTML file securely to authenticated users.
     */
    public function serveFile(Form $form, ProcurementFormTemplateRenderer $renderer)
    {
        if (!Storage::disk('private')->exists($form->path)) {
            abort(404, 'File not found on storage.');
        }

        $html = $renderer->render($form) ?? abort(404, 'Form template could not be rendered.');
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Update the specified form in storage.
     */
    public function update(Request $request, Form $form)
    {
        if (is_string($request->input('code'))) $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('forms', 'code')->ignore($form->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'published', 'unpublished'])],
            'file' => ['nullable', 'file', 'mimetypes:text/html,text/plain', 'mimes:html,htm', 'max:2048'],
            'page_size' => ['required', 'string', Rule::in(['A4', 'Letter', 'Legal', 'Folio', 'A3', 'A5', 'Custom'])],
            'custom_width' => ['nullable', 'required_if:page_size,Custom', 'numeric', 'min:1', 'max:2000'],
            'custom_height' => ['nullable', 'required_if:page_size,Custom', 'numeric', 'min:1', 'max:2000'],
            'margin_top' => ['required', 'numeric', 'min:0', 'max:500'],
            'margin_left' => ['required', 'numeric', 'min:0', 'max:500'],
            'margin_right' => ['required', 'numeric', 'min:0', 'max:500'],
            'margin_bottom' => ['required', 'numeric', 'min:0', 'max:500'],
        ]);

        $oldPath = null;
        $newPath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $oldPath = $form->path;
            $form->name = basename($file->getClientOriginalName());
            $newPath = $file->store('forms', 'private');
            $form->path = $newPath;
        }

        $form->code = $validated['code'];
        $form->description = $validated['description'] ?? null;
        $form->status = $validated['status'];
        $form->configuration = [
            'page_size' => $validated['page_size'],
            'custom_width' => $validated['custom_width'] ?? null,
            'custom_height' => $validated['custom_height'] ?? null,
            'margins' => [
                'top' => (float) $validated['margin_top'],
                'right' => (float) $validated['margin_right'],
                'bottom' => (float) $validated['margin_bottom'],
                'left' => (float) $validated['margin_left'],
            ],
        ];

        try {
            DB::transaction(fn () => $form->save());
        } catch (Throwable $exception) {
            if ($newPath) Storage::disk('private')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $form->path) Storage::disk('private')->delete($oldPath);

        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form updated successfully.');
    }

    /**
     * Remove the specified form from storage and database.
     */
    public function destroy(Form $form)
    {
        if ($form->path && Storage::disk('private')->exists($form->path)) {
            Storage::disk('private')->delete($form->path);
        }

        $form->delete();

        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form and associated HTML file deleted successfully.');
    }

    /**
     * Mark the form status as published.
     */
    public function publish(Form $form)
    {
        abort_if($form->status === 'archived', 422);
        $form->update(['status' => 'published']);

        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form published successfully.');
    }

    /**
     * Mark the form status as unpublished.
     */
    public function unpublish(Form $form)
    {
        abort_if($form->status === 'archived', 422);
        $form->update(['status' => 'unpublished']);

        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form unpublished successfully.');
    }
}
