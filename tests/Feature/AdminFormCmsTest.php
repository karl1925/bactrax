<?php

namespace Tests\Feature;

use App\Models\Procurement\RfqDocument;
use App\Models\ProcurementMode\Form;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProcurementFormTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFormCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_cms_saves_html_and_renderer_injects_safe_markup_into_marked_sections(): void
    {
        $this->seed();
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->put(route('admin.form-cms.update'), [
                'forms_header' => '<h1>Shared <em>header</em></h1><script>alert(1)</script>',
                'rfq_notice' => '<p>Notice HTML.</p>',
                'rfq_instructions' => '<p>Submit <strong>on time</strong>.</p>',
                'rfq_terms' => '<ol><li>Terms apply.</li></ol>',
            ])
            ->assertRedirect(route('admin.form-cms.index'));

        Storage::fake('private');
        Storage::disk('private')->put('forms/cms-test.html', '<main><section data-header data-title="REQUEST &amp; QUOTATION"></section><section data-rfq-notice></section><section data-rfq-instructions></section><section data-rfq-terms></section></main>');
        $form = new Form(['path' => 'forms/cms-test.html']);
        $html = app(ProcurementFormTemplateRenderer::class)->render($form);

        $this->assertStringContainsString('<h1>Shared <em>header</em></h1>', $html);
        $this->assertStringContainsString('<div style="display:flex;width:100%;min-height:35px;align-items:center;justify-content:center;background:#fff8d0;font-family:Arial,Helvetica,sans-serif;font-weight:800;text-align:center">REQUEST &amp; QUOTATION</div>', $html);
        $this->assertStringContainsString('<p>Notice HTML.</p>', $html);
        $this->assertStringContainsString('<p>Submit <strong>on time</strong>.</p>', $html);
        $this->assertStringContainsString('<ol><li>Terms apply.</li></ol>', $html);
        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertDatabaseHas('settings', ['key' => 'forms_header', 'value' => '<h1>Shared <em>header</em></h1><script>alert(1)</script>']);
    }

    public function test_rfq_document_admin_routes_support_create_update_and_delete(): void
    {
        $this->seed();
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.rfq-documents.store'), [
                'sequence' => 10,
                'title' => 'Business permit',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.rfq-documents.index'));

        $document = RfqDocument::query()->where('title', 'Business permit')->firstOrFail();
        $this->put(route('admin.rfq-documents.update', $document), [
            'sequence' => 2,
            'title' => 'Updated business permit',
            'is_active' => 0,
        ])->assertRedirect(route('admin.rfq-documents.index'));

        $document->refresh();
        $this->assertSame(5, $document->sequence);
        $this->assertSame('Updated business permit', $document->title);
        $this->assertFalse($document->is_active);

        $this->delete(route('admin.rfq-documents.destroy', $document))
            ->assertRedirect(route('admin.rfq-documents.index'));
        $this->assertDatabaseMissing('rfq_documents', ['id' => $document->id]);
    }

    public function test_rfq_documents_can_be_reordered_archived_and_restored(): void
    {
        $this->seed();
        $admin = $this->adminUser();
        $documents = RfqDocument::query()->where('is_active', true)->orderBy('sequence')->pluck('id')->all();
        $reorderedIds = array_reverse($documents);

        $this->actingAs($admin)
            ->patchJson(route('admin.rfq-documents.reorder'), ['order' => $reorderedIds])
            ->assertOk()
            ->assertJson(['ok' => true]);

        foreach ($reorderedIds as $index => $id) {
            $this->assertDatabaseHas('rfq_documents', ['id' => $id, 'sequence' => $index + 1, 'is_active' => true]);
        }

        $document = RfqDocument::query()->findOrFail($reorderedIds[0]);
        $this->patch(route('admin.rfq-documents.toggle', $document))
            ->assertRedirect(route('admin.rfq-documents.archive'));
        $this->assertDatabaseHas('rfq_documents', ['id' => $document->id, 'is_active' => false]);

        $this->get(route('admin.rfq-documents.archive'))->assertOk()->assertSee($document->title);
        $this->patch(route('admin.rfq-documents.toggle', $document))
            ->assertRedirect(route('admin.rfq-documents.index'));
        $this->assertDatabaseHas('rfq_documents', ['id' => $document->id, 'is_active' => true]);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        return $admin;
    }
}
