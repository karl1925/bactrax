<?php

namespace Tests\Feature;

use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementAttachment;
use App\Models\Procurement\RfqDocument;
use App\Models\ProcurementMode\{ProcurementMode, ProcurementModePhase};
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RfqDocumentSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfq_required_documents_are_seeded_and_selection_is_persisted(): void
    {
        $this->seed();

        $this->assertSame([
            "Latest Business/Mayor's Permit issued by the city or municipality where the principal place of business of the bidder is located;",
            'Professional License/CV (for Consulting Services)',
            'PCAB License (for Infrastructure Projects)',
            'PhilGEPS Certificate of Registration or PhilGEPS Registration Number',
        ], RfqDocument::query()->orderBy('sequence')->pluck('title')->all());
        RfqDocument::query()->where('sequence', 4)->update(['is_active' => false]);

        $user = User::factory()->create();
        $role = Role::query()->where('name', 'bac_secretariat')->firstOrFail();
        $user->roles()->attach($role->id);

        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $phase = ProcurementModePhase::query()
            ->where('procurement_mode_id', $mode->id)
            ->where('phase_sequence', 2)
            ->with(['roles', 'attachments.form'])
            ->firstOrFail();
        $attachment = $phase->attachments->first(fn($item) => $item->form?->code === 'RFQ');
        $this->assertNotNull($attachment);
        Storage::fake('procurements');

        $procurement = Procurement::create([
            'purpose' => 'RFQ required-document selection test',
            'procurement_mode_id' => $mode->id,
            'current_phase_id' => $phase->id,
            'status' => 'DRAFT',
            'created_by' => $user->id,
        ]);
        $pdfPath = "procurements/{$procurement->id}/attachments/rfq.pdf";
        Storage::disk('procurements')->put($pdfPath, '%PDF-1.4 stale');
        ProcurementAttachment::create([
            'procurement_id' => $procurement->id,
            'phase_id' => $phase->id,
            'attachment_id' => $attachment->id,
            'form_id' => $attachment->form_id,
            'is_completed' => true,
            'workflow_status' => 'READY',
            'file_name' => 'rfq.pdf',
            'file_path' => $pdfPath,
            'file_size' => 14,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $user->id,
        ]);
        $document = RfqDocument::query()->where('sequence', 1)->firstOrFail();
        $url = fn() => route('procurements.attachments.rfq-documents.toggle', [
            'procurement' => $procurement,
            'attachmentId' => $attachment->id,
            'rfqDocumentId' => $document->id,
        ]);

        $this->actingAs($user)->post($url(), ['selected' => true])
            ->assertOk()
            ->assertJson(['ok' => true, 'selected' => true]);
        $this->assertDatabaseHas('procurement_required_docs', [
            'procurement_id' => $procurement->id,
            'rfq_document_id' => $document->id,
        ]);
        $showResponse = $this->get(route('procurements.show', $procurement))->assertOk();
        $page = new \DOMDocument();
        $previousLibxmlErrors = libxml_use_internal_errors(true);
        $page->loadHTML($showResponse->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlErrors);
        $template = (new \DOMXPath($page))
            ->query('//template[@id="attachment-form-template-'.$attachment->id.'"]')
            ->item(0);
        $this->assertNotNull($template, 'The RFQ template should be embedded in the procurement workspace.');
        $context = json_decode($template->getAttribute('data-form-context'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(3, $context['rfq']['required_documents']);
        $this->assertSame(
            RfqDocument::query()->where('is_active', true)->orderBy('sequence')->pluck('title')->all(),
            array_column($context['rfq']['required_documents'], 'label'),
        );
        $this->assertNotContains(4, array_column($context['rfq']['required_documents'], 'id'));
        $this->assertSame('✓', $context['rfq']['required_documents'][0]['checked_mark']);
        $this->assertContains($document->id, $context['procurement']['rfq_documents']);
        $this->assertDatabaseHas('procurement_attachments', [
            'procurement_id' => $procurement->id,
            'attachment_id' => $attachment->id,
            'workflow_status' => 'INCOMPLETE',
            'is_completed' => false,
            'file_path' => null,
        ]);
        Storage::disk('procurements')->assertMissing($pdfPath);

        $this->post($url(), ['selected' => false])
            ->assertOk()
            ->assertJson(['ok' => true, 'selected' => false]);
        $this->assertDatabaseMissing('procurement_required_docs', [
            'procurement_id' => $procurement->id,
            'rfq_document_id' => $document->id,
        ]);
    }

    public function test_inactive_rfq_documents_cannot_be_selected(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'bac_secretariat')->value('id'));
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $phase = ProcurementModePhase::query()
            ->where('procurement_mode_id', $mode->id)
            ->where('phase_sequence', 2)
            ->with('attachments.form')
            ->firstOrFail();
        $attachment = $phase->attachments->first(fn($item) => $item->form?->code === 'RFQ');
        $document = RfqDocument::query()->firstOrFail();
        $document->update(['is_active' => false]);
        $procurement = Procurement::create([
            'purpose' => 'RFQ inactive-document selection test',
            'procurement_mode_id' => $mode->id,
            'current_phase_id' => $phase->id,
            'status' => 'DRAFT',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('procurements.attachments.rfq-documents.toggle', [
                'procurement' => $procurement,
                'attachmentId' => $attachment->id,
                'rfqDocumentId' => $document->id,
            ]), ['selected' => true])
            ->assertNotFound();

        $this->assertDatabaseMissing('procurement_required_docs', [
            'procurement_id' => $procurement->id,
            'rfq_document_id' => $document->id,
        ]);
    }
}
