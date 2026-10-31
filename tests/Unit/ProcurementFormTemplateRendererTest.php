<?php

namespace Tests\Unit;

use App\Models\ProcurementMode\Form;
use App\Services\ProcurementFormTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcurementFormTemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_templates_are_rendered_as_inert_sanitized_html(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('forms/untrusted.html', '<div onclick="alert(1)">Safe text</div><script>window.compromised=true</script><iframe src="https://evil.test"></iframe>');

        $html = app(ProcurementFormTemplateRenderer::class)->render(new Form(['path' => 'forms/untrusted.html']));

        $this->assertStringContainsString('Safe text', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('<iframe', strtolower($html));
    }

    public function test_classless_template_root_keeps_inline_body_presentation(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('forms/pr.html', '<html><body style="font-family:Arial;font-size:12px;color:#111"><main data-template-code="PR" data-template-version="1" style="width:100%">PR form</main></body></html>');

        $html = app(ProcurementFormTemplateRenderer::class)->render(new Form(['path' => 'forms/pr.html']));

        $this->assertStringContainsString('data-template-code="PR"', $html);
        $this->assertStringContainsString('font-family:Arial;font-size:12px', $html);
        $this->assertStringContainsString('width:100%', $html);
    }

    public function test_legacy_pr_collections_are_escaped_and_supported_without_blade_execution(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('forms/pr.html', <<<'HTML'
<select>@foreach($fundClusters as $fundCluster)<option value="{{ $fundCluster->name }}">{{ $fundCluster->name }}</option>@endforeach</select><p>{{ $pr_number }}</p><button onclick="handleMenuAction('add_above', this)">Add Item Above</button><button data-pr-action="delete" onclick="window.evil()">Delete</button>
HTML);
        $fundCluster = (object) ['name' => '<script>alert(1)</script>'];

        $html = app(ProcurementFormTemplateRenderer::class)->render(
            new Form(['path' => 'forms/pr.html']),
            [],
            [$fundCluster],
            'PR00001'
        );

        $this->assertStringContainsString('PR00001', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('data-pr-action="add_above"', $html);
        $this->assertStringNotContainsString('data-pr-action="delete"', $html);
        $this->assertStringNotContainsString('<script', strtolower($html));
    }
}
