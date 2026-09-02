<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Procurement;
use App\Models\ProcurementDocument;
use App\Models\ProcurementModePhaseDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class ProcurementDocumentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Upload and attach a document to a procurement project phase.
     */
    public function store(Request $request, Procurement $procurement)
    {
        $request->validate([
            'phase_id' => ['required', 'exists:procurement_mode_phases,id'],
            'document_type_name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,png', 'max:10240'], // 10MB limit
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getClientMimeType();

        // Generate clean file path: procurement_docs/{procurement_id}/{phase_id}/timestamp_filename
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $filePath = "procurements/{$procurement->id}/{$request->input('phase_id')}/{$fileName}";

        // Store file privately
        Storage::disk('procurements')->put($filePath, file_get_contents($file));

        // Create Database Record
        ProcurementDocument::create([
            'procurement_id' => $procurement->id,
            'phase_id' => $request->input('phase_id'),
            'file_name' => $request->input('document_type_name') . ' - ' . $originalName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by' => Auth::user()->id,
        ]);

        return back()->with('success', "Document '{$originalName}' uploaded and attached successfully.");
    }

    /**
     * Securely stream or download an attached procurement file.
     */
    public function download(Procurement $procurement, ProcurementDocument $document): StreamedResponse
    {
        // Enforce Office Scope and View Security
        $this->authorize('view', $procurement);

        if (!Storage::disk('procurements')->exists($document->file_path)) {
            abort(404, 'The requested document file was not found on the server.');
        }

        return Storage::disk('procurements')->download(
            $document->file_path,
            $document->file_name
        );
    }

    /**
     * Delete an attached document (allowed only in draft status or by creator/admin).
     */
    public function destroy(Procurement $procurement, ProcurementDocument $document)
    {
        if ($procurement->status === 'COMPLETED') {
            return back()->with('error', 'Cannot delete document attachments from completed procurements.');
        }

        // Delete physical file
        if (Storage::disk('procurements')->exists($document->file_path)) {
            Storage::disk('procurements')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document attachment deleted successfully.');
    }
}
