<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementAttachment;
use App\Models\ProcurementMode\Attachment;
use App\Models\ProcurementMode\Form;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProcurementFormPdfRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcurementFormPdfCompileTest extends TestCase
{
    use RefreshDatabase;

    public function test_compiled_form_html_is_rendered_and_saved_through_the_attachment_flow(): void
    {
        $this->seed();
        Storage::fake('procurements');

        $admin = User::factory()->create();
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $phase = $mode->phases()->orderBy('phase_sequence')->firstOrFail();
        $admin->roles()->attach($phase->roles()->value('roles.id'));
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $procurement = Procurement::create([
            'procurement_mode_id' => $mode->id,
            'status' => 'DRAFT',
            'created_by' => $admin->id,
        ]);
        $form = Form::create([
            'code' => 'PDF_TEST',
            'name' => 'PDF test form',
            'path' => 'pdf-test.html',
            'status' => 'active',
        ]);
        $attachment = Attachment::create([
            'procurement_mode_phase_id' => $phase->id,
            'name' => 'Test attachment',
            'form_id' => $form->id,
            'is_mandatory' => true,
            'is_active' => true,
        ]);
        $prForm = Form::query()->where('code', 'PR')->firstOrFail();
        Setting::set('pr_form', $prForm->id, 'procurement');

        $renderer = Mockery::mock(ProcurementFormPdfRenderer::class);
        $renderer->shouldReceive('render')
            ->twice()
            ->andReturn("%PDF-1.4\nSelectable text");
        $this->app->instance(ProcurementFormPdfRenderer::class, $renderer);

        $this->actingAs($admin)
            ->postJson(route('procurements.attachments.save', [$procurement, $attachment->id]), [
                'fields' => '{}',
                'is_complete' => true,
                'is_compiled' => true,
                'rendered_html' => '<html><body>Selectable text</body></html>',
                'pdf_page_width' => 210,
                'pdf_page_height' => 297,
                'pdf_margin_top' => 10,
                'pdf_margin_right' => 10,
                'pdf_margin_bottom' => 10,
                'pdf_margin_left' => 10,
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'is_ready' => true, 'form_complete' => true]);

        $savedAttachment = ProcurementAttachment::query()
            ->where('procurement_id', $procurement->id)
            ->where('attachment_id', $attachment->id)
            ->firstOrFail();

        $this->assertSame('application/pdf', $savedAttachment->mime_type);
        $this->assertSame('Test_attachment.pdf', $savedAttachment->file_name);
        Storage::disk('procurements')->assertExists($savedAttachment->file_path);
        $this->assertSame("%PDF-1.4\nSelectable text", Storage::disk('procurements')->get($savedAttachment->file_path));

        $office = Office::query()->firstOrFail();
        $this->actingAs($admin)
            ->postJson(route('procurements.attachments.save', [$procurement, 'pr']), [
                'fields' => json_encode([
                    'purpose' => 'Selectable PR PDF',
                    'fund_cluster' => 'REGULAR',
                    'end_user_office_id' => $office->id,
                    'pr_date' => '2025-01-01',
                    'responsibility_code' => 'PDF-001',
                    'items[0][unit]' => 'pc',
                    'items[0][description]' => 'Test item',
                    'items[0][quantity]' => '1',
                    'items[0][cost]' => '10',
                ], JSON_THROW_ON_ERROR),
                'is_complete' => true,
                'is_compiled' => true,
                'rendered_html' => '<html><body>Selectable PR text</body></html>',
                'pdf_page_width' => 210,
                'pdf_page_height' => 297,
                'pdf_margin_top' => 10,
                'pdf_margin_right' => 10,
                'pdf_margin_bottom' => 10,
                'pdf_margin_left' => 10,
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'is_ready' => true, 'form_complete' => true]);

        $savedPr = ProcurementAttachment::query()
            ->where('procurement_id', $procurement->id)
            ->whereNull('attachment_id')
            ->where('form_id', $prForm->id)
            ->firstOrFail();
        $this->assertSame('Purchase Request.pdf', $savedPr->file_name);
        Storage::disk('procurements')->assertExists($savedPr->file_path);
    }
}
