<?php 

namespace App\Http\Controllers;

use App\Models\ProcurementMode\Attachment;
use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcurementAttachmentController extends Controller
{
    /**
     * Store a newly uploaded attachment and map its required signatories.
     */
    public function store(Request $request, Procurement $procurement)
    {
        $request->validate([
            'phase_id' => ['required', 'exists:procurement_mode_phases,id'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'], // Procurement attachments are PDF documents.
            'attachment_requirement_id' => ['nullable', 'exists:attachments,id'],
        ]);

        return DB::transaction(function () use ($request, $procurement) {
            $phase = $procurement->mode?->phases()->findOrFail($request->input('phase_id'));
            abort_unless($phase && $request->user()->canActOnPhase($phase), 403, 'You are not assigned to this phase.');
            $requirement = $request->filled('attachment_requirement_id')
                ? Attachment::with('signatories')->whereKey($request->input('attachment_requirement_id'))->where('procurement_mode_phase_id', $phase->id)->firstOrFail()
                : null;
            if ($requirement?->form_id) abort(422, 'This attachment uses a bound form. Open the form and compile its PDF.');

            $file = $request->file('file');
            $path = $file->store("procurements/{$procurement->id}/attachments", 'procurements');

            $procurementAttachment = $requirement
                ? ProcurementAttachment::firstOrNew(['procurement_id' => $procurement->id, 'attachment_id' => $requirement->id])
                : new ProcurementAttachment(['procurement_id' => $procurement->id]);
            if ($procurementAttachment->file_path) {
                $procurementAttachment->signatories()->delete();
                Storage::disk('procurements')->delete($procurementAttachment->file_path);
            }
            $procurementAttachment->fill([
                'procurement_id' => $procurement->id,
                'phase_id' => $request->input('phase_id'),
                'attachment_id' => $requirement?->id,
                'form_id' => null,
                'is_completed' => true,
                'workflow_status' => $requirement?->signatories->isNotEmpty() ? 'READY' : 'COMPLETED',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'uploaded_by' => Auth::id(),
            ])->save();

            return back()->with('success', 'Attachment uploaded successfully.');
        });
    }

    /**
     * Download an uploaded attachment file.
     */
    public function download(Request $request, ProcurementAttachment $attachment)
    {
        abort_unless($request->user()->can('view', $attachment->procurement), 403);
        if (!Storage::disk('procurements')->exists($attachment->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('procurements')->download($attachment->file_path, $attachment->file_name, ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    /**
     * Delete an uploaded attachment and its file from storage.
     */
    public function destroy(Request $request, ProcurementAttachment $attachment)
    {
        abort_unless($request->user()->can('view', $attachment->procurement), 403);
        if ($attachment->file_path && Storage::disk('procurements')->exists($attachment->file_path)) {
            Storage::disk('procurements')->delete($attachment->file_path);
        }

        $attachment->delete();

        return back()->with('success', 'Attachment deleted successfully.');
    }

    /**
     * Sign a procurement attachment signatory step.
     */
    public function sign(ProcurementAttachmentSignatory $signatory)
    {
        // Authorize current user matches the assigned signatory
        if ($signatory->signatory_id !== Auth::id()) {
            return back()->with('error', 'You are not authorized to sign this attachment step.');
        }

        // Enforce sequential signing order
        $hasUnsignedPredecessors = ProcurementAttachmentSignatory::where('procurement_attachments_id', $signatory->procurement_attachments_id)
            ->where('sequence', '<', $signatory->sequence)
            ->where('is_signed', false)
            ->exists();

        if ($hasUnsignedPredecessors) {
            return back()->with('error', 'Previous signatories must sign before you can sign this document.');
        }

        $signatory->update([
            'is_signed' => true,
            'signed_at' => now(),
        ]);

        return back()->with('success', 'Document attachment signed successfully.');
    }
}
