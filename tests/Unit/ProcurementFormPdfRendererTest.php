<?php

namespace Tests\Unit;

use App\Services\ProcurementFormPdfRenderer;
use PHPUnit\Framework\TestCase;

class ProcurementFormPdfRendererTest extends TestCase
{
    public function test_it_removes_executable_and_remote_resources_but_keeps_form_content(): void
    {
        $html = <<<'HTML'
            <!doctype html>
            <html>
                <head>
                    <meta http-equiv="refresh" content="0;url=http://127.0.0.1/private">
                    <style>
                        @import url("https://example.invalid/remote.css");
                        .logo { background-image: url("http://127.0.0.1/private"); color: #123456; }
                    </style>
                </head>
                <body>
                    <img src="https://example.invalid/logo.png">
                    <img src="data:image/png;base64,aGVsbG8=">
                    <p onclick="alert(1)" style="color: #123456">Selectable form text</p>
                    <script>document.cookie</script>
                    <iframe src="http://127.0.0.1/"></iframe>
                </body>
            </html>
            HTML;

        $sanitized = (new ProcurementFormPdfRenderer)->sanitize($html);

        $this->assertStringContainsString('Selectable form text', $sanitized);
        $this->assertStringContainsString('data:image/png;base64,aGVsbG8=', $sanitized);
        $this->assertStringContainsString('Content-Security-Policy', $sanitized);
        $this->assertStringNotContainsString('example.invalid', $sanitized);
        $this->assertStringNotContainsString('127.0.0.1', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('document.cookie', $sanitized);
    }
}
