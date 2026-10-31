<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelPdf\Facades\Pdf;

class ProcurementFormPdfRenderer
{
    public function render(
        string $html,
        float $pageWidth,
        float $pageHeight,
        float $marginTop,
        float $marginRight,
        float $marginBottom,
        float $marginLeft,
    ): string {
        if (
            $marginLeft + $marginRight >= $pageWidth
            || $marginTop + $marginBottom >= $pageHeight
        ) {
            throw ValidationException::withMessages([
                'rendered_html' => 'The PDF page margins must be smaller than the page dimensions.',
            ]);
        }

        $pdf = Pdf::html($this->sanitize($html))
            ->driver('chrome')
            ->paperSize($pageWidth, $pageHeight, 'mm')
            ->margins($marginTop, $marginRight, $marginBottom, $marginLeft, 'mm')
            ->generatePdfContent();

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new \RuntimeException('Chromium returned an invalid PDF document.');
        }

        return $pdf;
    }

    public function sanitize(string $html): string
    {
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument('1.0', 'UTF-8');
            $document->loadHTML(
                '<?xml encoding="UTF-8">'.$html,
                LIBXML_HTML_NODEFDTD | LIBXML_NONET,
            );
            $xpath = new DOMXPath($document);

            foreach ($xpath->query('//script|//iframe|//object|//embed|//base|//link|//meta|//video|//audio|//source|//track|//applet|//frame|//frameset|//portal') as $element) {
                $element->parentNode?->removeChild($element);
            }

            foreach ($xpath->query('//*') as $element) {
                if (! $element instanceof DOMElement) {
                    continue;
                }

                foreach (iterator_to_array($element->attributes) as $attribute) {
                    $name = strtolower($attribute->name);

                    if (
                        str_starts_with($name, 'on')
                        || in_array($name, ['srcdoc', 'srcset', 'action', 'formaction', 'poster', 'background', 'href', 'xlink:href'], true)
                    ) {
                        $element->removeAttributeNode($attribute);

                        continue;
                    }

                    if ($name === 'src' && ! $this->isEmbeddedImage($attribute->value)) {
                        $element->removeAttributeNode($attribute);

                        continue;
                    }

                    if ($name === 'style') {
                        $element->setAttribute('style', $this->sanitizeCss($attribute->value));
                    }
                }
            }

            foreach ($xpath->query('//style') as $style) {
                $style->textContent = $this->sanitizeCss($style->textContent);
            }

            $head = $document->getElementsByTagName('head')->item(0);
            if ($head) {
                $policy = $document->createElement('meta');
                $policy->setAttribute('http-equiv', 'Content-Security-Policy');
                $policy->setAttribute('content', "default-src 'none'; img-src data:; style-src 'unsafe-inline'; font-src data:");
                $head->insertBefore($policy, $head->firstChild);
            }

            return $document->saveHTML();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function isEmbeddedImage(string $source): bool
    {
        if (! preg_match('/\Adata:image\/(?:png|jpe?g|gif|webp);base64,([A-Za-z0-9+\/=\s]+)\z/i', $source, $matches)) {
            return false;
        }

        return base64_decode(preg_replace('/\s+/', '', $matches[1]), true) !== false;
    }

    private function sanitizeCss(string $css): string
    {
        $css = preg_replace('/@import\b[^;]*;?/i', '', $css) ?? '';
        $css = preg_replace('/url\s*\([^)]*\)/i', 'none', $css) ?? '';

        return preg_replace('/(?:expression\s*\([^)]*\)|-moz-binding\s*:[^;]+;?|behavior\s*:[^;]+;?)/i', '', $css) ?? '';
    }
}
