<?php

namespace App\Services;

use App\Models\ProcurementMode\Form;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/** Renders stored templates as inert HTML; template files are data and never Blade code. */
class ProcurementFormTemplateRenderer
{
    public function render(?Form $form, iterable $offices = [], iterable $fundClusters = [], ?string $prNumber = null): ?string
    {
        if (! $form?->path || ! Storage::disk('private')->exists($form->path)) {
            return null;
        }

        $html = Storage::disk('private')->get($form->path);
        if (! is_string($html) || strlen($html) > 2_000_000) {
            return null;
        }

        $html = $this->renderLegacyBindings($html, $offices, $fundClusters, $prNumber);
        $headerContent = Setting::get('forms_header', '');
        $html = preg_replace_callback(
            '~(<section\b(?=[^>]*\bdata-header(?:\s|=|>))([^>]*)>).*?(</section\s*>)~is',
            static function (array $matches) use ($headerContent): string {
                $title = preg_match('/\bdata-title\s*=\s*(["\'])(.*?)\1/is', $matches[2], $titleMatch)
                    ? htmlspecialchars(html_entity_decode($titleMatch[2], ENT_QUOTES | ENT_HTML5), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    : '';
                $titleMarkup = $title === '' ? '' : '<div style="display:flex;width:100%;min-height:35px;align-items:center;justify-content:center;background:#fff8d0;font-family:Arial,Helvetica,sans-serif;font-weight:800;text-align:center">'.$title.'</div>';

                return $matches[1].(string) $headerContent.$titleMarkup.$matches[3];
            },
            $html
        ) ?? $html;
        foreach ([
            'data-rfq-notice' => Setting::get('rfq_notice', ''),
            'data-rfq-instructions' => Setting::get('rfq_instructions', ''),
            'data-rfq-terms' => Setting::get('rfq_terms', ''),
        ] as $marker => $content) {
            $pattern = '~(<section\b(?=[^>]*\b'.preg_quote($marker, '~').'(?:\s|=|>))[^>]*>).*?(</section\s*>)~is';
            $html = preg_replace_callback(
                $pattern,
                static fn (array $matches): string => $matches[1].(string) $content.$matches[2],
                $html
            ) ?? $html;
        }

        return $this->sanitize($html);
    }

    private function renderLegacyBindings(string $html, iterable $offices, iterable $fundClusters, ?string $prNumber): string
    {
        foreach ([
            'offices' => [$offices, 'office'],
            'fundClusters' => [$fundClusters, 'fundCluster'],
        ] as $collectionName => [$items, $itemName]) {
            $pattern = '/@foreach\s*\(\$'.preg_quote($collectionName, '/').'\s+as\s+\$'.preg_quote($itemName, '/').'\)(.*?)@endforeach/s';
            $html = preg_replace_callback($pattern, function (array $matches) use ($items, $itemName): string {
                $rendered = '';
                foreach ($items as $item) {
                    $rendered .= preg_replace_callback(
                        '/\{\{\s*\$'.preg_quote($itemName, '/').'->([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/',
                        static fn (array $binding): string => htmlspecialchars((string) data_get($item, $binding[1], ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        $matches[1]
                    ) ?? '';
                }

                return $rendered;
            }, $html) ?? $html;
        }

        return preg_replace(
            '/\{\{\s*\$pr_number\s*\}\}/',
            htmlspecialchars($prNumber ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $html
        ) ?? $html;
    }

    private function sanitize(string $html): string
    {
        if (! class_exists(\DOMDocument::class)) {
            throw new \RuntimeException('The DOM extension is required to safely render procurement form templates.');
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($document);
        foreach (iterator_to_array($xpath->query('//script|//iframe|//frame|//frameset|//object|//embed|//applet|//portal|//video|//audio|//source|//track|//base|//meta|//link|//svg|//math|//form') ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }
        foreach (iterator_to_array($xpath->query('//style') ?: []) as $style) {
            $style->textContent = preg_replace('/@import[^;]+;?|url\s*\([^)]*\)|expression\s*\([^)]*\)/i', '', $style->textContent) ?? '';
        }

        foreach (iterator_to_array($xpath->query('//*') ?: []) as $element) {
            if (! $element instanceof \DOMElement) continue;
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (str_starts_with($name, 'on')) {
                    if ($name === 'onclick' && preg_match('/^handleMenuAction\(\s*[\'"]([a-z_]+)[\'"]\s*,\s*this\s*\)$/i', $value, $matches) === 1
                        && in_array($matches[1], ['add_above', 'add_below', 'add_lot_above', 'add_lot_below', 'add_member_above', 'add_member_below', 'delete', 'delete_lot'], true)) {
                        $element->setAttribute('data-pr-action', $matches[1]);
                    } elseif ($name === 'onclick' && $element->hasAttribute('data-pr-action')) {
                        $element->removeAttribute('data-pr-action');
                    }
                    $element->removeAttributeNode($attribute);
                    continue;
                }
                if (in_array($name, ['srcdoc', 'action', 'formaction', 'formtarget', 'srcset', 'ping', 'background', 'xmlns'], true)) {
                    $element->removeAttributeNode($attribute);
                    continue;
                }
                if ($name === 'data-pr-action' && ($element->tagName !== 'button' || ! in_array($value, ['add_above', 'add_below', 'add_lot_above', 'add_lot_below', 'add_member_above', 'add_member_below', 'delete', 'delete_lot'], true))) {
                    $element->removeAttributeNode($attribute);
                    continue;
                }
                if ($name === 'data-pr-add-row' && ($element->tagName !== 'button' || ! in_array($value, ['standalone', 'lot'], true))) {
                    $element->removeAttributeNode($attribute);
                    continue;
                }
                if (in_array($name, ['href', 'src', 'xlink:href'], true)) {
                    $url = preg_replace('/[\x00-\x20]+/', '', $value) ?? $value;
                    $hasScheme = preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1;
                    $safeDataImage = $name === 'src' && preg_match('/^data:image\/(?:png|gif|jpeg|webp);base64,/i', $url) === 1;
                    $safeMailLink = $name === 'href' && preg_match('/^mailto:/i', $url) === 1;
                    $safeRelativeUrl = ! $hasScheme && ! str_starts_with($url, '//');
                    if (! $safeRelativeUrl && ! $safeDataImage && ! $safeMailLink) {
                        $element->removeAttributeNode($attribute);
                        continue;
                    }
                }
                if ($name === 'target') {
                    $element->removeAttributeNode($attribute);
                }
                if ($name === 'style' && preg_match('/url\s*\(|expression\s*\(|@import/i', $value)) {
                    $element->removeAttributeNode($attribute);
                }
            }

        }

        // Templates are moved into the viewer's generated page containers, so a
        // style inherited only from <body> would otherwise disappear. Carry the
        // source body's inline presentation onto the printable form root while
        // keeping the template's original inline layout as the source of truth.
        $body = $document->getElementsByTagName('body')->item(0);
        $main = $xpath->query('//main[@data-template-code or contains(concat(" ", normalize-space(@class), " "), " rfq-document ") or contains(concat(" ", normalize-space(@class), " "), " form-document ")]')->item(0);
        if ($body instanceof \DOMElement && $main instanceof \DOMElement) {
            $bodyStyle = trim($body->getAttribute('style'));
            if ($bodyStyle !== '') {
                $mainStyle = trim($main->getAttribute('style'));
                $main->setAttribute('style', trim($bodyStyle.'; '.$mainStyle, " ;\t\n\r\0\x0B"));
            }
        }

        return $document->saveHTML() ?: '';
    }
}
