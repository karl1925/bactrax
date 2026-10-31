<?php

namespace Database\Seeders;

use App\Models\ProcurementMode\Form;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProcurementFormSeeder extends Seeder
{
    public function run(): void
    {
        $this->assertImportedTemplatesMatchSchema();
        $schema = json_decode((string) file_get_contents(database_path('form-schemas/procurement.json')), true, flags: JSON_THROW_ON_ERROR);

        $forms = [
            'PR' => [
                'name' => 'Purchase Request',
                'description' => 'Purchase Request form. Declared data bindings map procurement details and repeatable items to their owning procurement records.',
                'template_data' => [],
            ],
            'RFQ' => [
                'name' => 'Request for Quotation',
                'description' => 'Request for Quotation form. Procurement fields and editable item ABC amounts sync to the procurement record; required supplier documents are configured in the RFQ document catalog.',
                'template_data' => [
                    'rfq' => [
                        'bac_secretariat_name' => 'Head, BAC Secretariat',
                        'contact_email' => 'bac.r2@dict.gov.ph',
                        'bac_chairperson' => 'BAC Chairperson',
                    ],
                ],
            ],
            'MIN' => [
                'name' => 'Minutes of Opening of Bid Price Quotation',
                'description' => 'Minutes of quotation opening, attendance, supplier bids, and responsive-bid evaluation.',
                'template_data' => [],
            ],
            'ABS' => [
                'name' => 'Abstract of Awards / Canvass',
                'description' => 'Supplier quotation comparison, canvass totals, and lowest calculated responsive bid.',
                'template_data' => [],
            ],
            'RES' => [
                'name' => 'BAC Resolution Recommending Award',
                'description' => 'BAC resolution recording the award recommendation and approving authority.',
                'template_data' => [],
            ],
        ];

        foreach ($forms as $code => $formData) {
            $source = base_path("import_forms/{$code}.html");
            if (! is_file($source)) {
                throw new \RuntimeException("Cannot seed the {$code} form: import_forms/{$code}.html is missing.");
            }

            $path = "forms/{$code}.html";
            Storage::disk('private')->put($path, file_get_contents($source));

            Form::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $formData['name'],
                    'description' => $formData['description'],
                    'path' => $path,
                    'status' => 'published',
                    'configuration' => [
                        'page_size' => 'A4',
                        'margins' => ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10],
                        'renderer' => 'data-bindings-v1',
                        'schema' => [
                            'file' => 'database/form-schemas/procurement.json',
                            'template' => "formTemplates.{$code}",
                            'version' => (int) ($schema['formTemplates'][$code]['schemaVersion'] ?? 1),
                        ],
                        'template_data' => $formData['template_data'],
                    ],
                ],
            );
        }

        // The master seeder creates the setting before form rows exist. Bind its
        // default now, after the published PR form has been created.
        $prForm = Form::query()->where('code', 'PR')->where('status', 'published')->firstOrFail();
        $configuredFormId = (int) Setting::get('pr_form', 0);
        $configuredFormIsPublished = $configuredFormId > 0
            && Form::query()->whereKey($configuredFormId)->where('status', 'published')->exists();

        if (! $configuredFormIsPublished) {
            Setting::set('pr_form', $prForm->id, 'procurement', 'Active Purchase Request Form Template');
        }
    }

    /**
     * Keep every imported form source aligned with the shared JSON contract,
     * including PR, which is configured separately from phase form seeding.
     */
    private function assertImportedTemplatesMatchSchema(): void
    {
        $schemaPath = database_path('form-schemas/procurement.json');
        $schema = json_decode((string) file_get_contents($schemaPath), true, flags: JSON_THROW_ON_ERROR);
        $templates = $schema['formTemplates'] ?? [];
        $expectedCodes = ['PR', 'RFQ', 'MIN', 'ABS', 'RES'];

        if (array_diff($expectedCodes, array_keys($templates)) !== []) {
            throw new \RuntimeException('The procurement form schema must declare PR, RFQ, MIN, ABS, and RES templates.');
        }

        foreach ($expectedCodes as $code) {
            $source = base_path("import_forms/{$code}.html");
            if (! is_file($source)) {
                throw new \RuntimeException("Cannot validate {$code}: import_forms/{$code}.html is missing.");
            }

            $html = (string) file_get_contents($source);
            if (preg_match('/@(?:foreach|endforeach|selected)\b|\bon[a-z]+\s*=/i', $html)) {
                throw new \RuntimeException("The {$code} form contains executable template directives or inline handlers. Use declared data bindings and renderer actions instead.");
            }
            if (! preg_match('/data-template-code=["\']'.preg_quote($code, '/').'["\']/i', $html)
                || ! preg_match('/data-template-version=["\']'.preg_quote((string) ($templates[$code]['schemaVersion'] ?? ''), '/').'["\']/i', $html)) {
                throw new \RuntimeException("The {$code} template code/version does not match its JSON schema entry.");
            }
            if (! preg_match("~<section\\b(?=[^>]*\\bdata-header\\b)(?=[^>]*\\bdata-title=[\"'][^\"']+[\"'])[^>]*>~i", $html)) {
                throw new \RuntimeException("The {$code} template must declare its title in a shared data-header section.");
            }

            if ($code === 'PR') {
                $markup = preg_replace('~<style\b[^>]*>.*?</style>~is', '', $html) ?? $html;
                preg_match_all('/\b(data-[a-z0-9-]+)\s*=/i', $markup, $dataAttributes);
                $unsupportedAttributes = array_values(array_diff(array_unique(array_map('strtolower', $dataAttributes[1] ?? [])), ['data-input', 'data-print', 'data-each', 'data-format', 'data-value', 'data-template-code', 'data-template-version', 'data-header', 'data-title']));
                if ($unsupportedAttributes !== []) {
                    throw new \RuntimeException('The PR template may use only declared data bindings, display formats, and initial cell values. Move renderer behavior into the JSON schema. Found: '.implode(', ', $unsupportedAttributes));
                }
            }

            $contentDefaults = FormContentSeeder::defaults();
            $contractHtml = $html.($contentDefaults['forms_header'] ?? '');
            if ($code === 'RFQ') {
                $contractHtml .= ($contentDefaults['rfq_notice'] ?? '').($contentDefaults['rfq_instructions'] ?? '').($contentDefaults['rfq_terms'] ?? '');
            }
            $declared = $templates[$code]['bindings'] ?? [];
            $actual = [];
            foreach (['data-input', 'data-print', 'data-each', 'data-show-if', 'data-show-if-not', 'data-default-from', 'data-clear-if', 'data-clear-if-not'] as $attribute) {
                preg_match_all('/\\b'.preg_quote($attribute, '/').'\\s*=\\s*(["\\\'])(.*?)\\1/is', $contractHtml, $matches);
                $values = array_values(array_unique(array_map(static fn (string $value): string => html_entity_decode($value, ENT_QUOTES | ENT_HTML5), $matches[2] ?? [])));
                sort($values);
                if ($values !== []) $actual[$attribute] = $values;
            }
            ksort($actual);
            ksort($declared);
            if ($actual !== $declared) {
                throw new \RuntimeException("The {$code} HTML bindings do not match formTemplates.{$code} in {$schemaPath}. Update the JSON schema with the template.");
            }

            if ($code !== 'PR') {
                $actualFields = $this->templateFieldContracts($contractHtml);
                $declaredFields = $templates[$code]['fields'] ?? [];
                if ($actualFields != $declaredFields) {
                    throw new \RuntimeException("The {$code} input field metadata does not match formTemplates.{$code}.fields in {$schemaPath}. Update the JSON schema with the template.");
                }
            }
            if ($this->templateDisplayFormats($contractHtml) != ($templates[$code]['displayFormats'] ?? [])) {
                throw new \RuntimeException("The {$code} display formats do not match formTemplates.{$code}.displayFormats in {$schemaPath}.");
            }

            $actualActions = [];
            foreach (['data-pr-action', 'data-pr-add-row'] as $attribute) {
                preg_match_all('/\\b'.preg_quote($attribute, '/').'\\s*=\\s*(["\\\'])(.*?)\\1/is', $html, $matches);
                $values = array_values(array_unique($matches[2] ?? []));
                sort($values);
                if ($values !== []) $actualActions[$attribute] = $values;
            }
            $declaredActions = $schema['templateActions'][$code] ?? [];
            $undeclaredActions = [];
            foreach ($actualActions as $attribute => $values) {
                $undeclaredActions[$attribute] = array_values(array_diff($values, $declaredActions[$attribute] ?? []));
            }
            if (collect($undeclaredActions)->flatten()->isNotEmpty()) {
                throw new \RuntimeException("The {$code} renderer actions do not match templateActions in {$schemaPath}.");
            }

            $allDeclaredPaths = $schema['templateBindings'] ?? [];
            foreach ($actual as $paths) {
                foreach ($paths as $path) {
                    if (! array_key_exists($path, $allDeclaredPaths)) {
                        throw new \RuntimeException("The {$code} template uses undeclared binding '{$path}'. Add it to templateBindings in {$schemaPath}.");
                    }
                }
            }
        }
    }

    /** Extract the declarative metadata attached to each editable binding. */
    private function templateFieldContracts(string $html): array
    {
        preg_match_all('/<[a-z][a-z0-9:-]*\\b(?=[^>]*\\bdata-input\\s*=)[^>]*>/is', $html, $elements);
        $fields = [];
        foreach ($elements[0] ?? [] as $element) {
            preg_match_all('/([a-z][a-z0-9:-]*)\\s*(?:=\\s*(["\\\'])(.*?)\\2)?/is', $element, $attributes, PREG_SET_ORDER);
            $values = [];
            foreach ($attributes as $attribute) {
                $values[strtolower($attribute[1])] = isset($attribute[3]) ? html_entity_decode($attribute[3], ENT_QUOTES | ENT_HTML5) : true;
            }
            $path = $values['data-input'] ?? null;
            if (! is_string($path) || $path === '') continue;
            $contract = [];
            foreach ([
                'data-input-type' => 'inputType', 'data-source' => 'source', 'data-min' => 'min',
                'data-max' => 'max', 'data-step' => 'step', 'data-unit' => 'unit',
                'data-default' => 'default', 'data-default-from' => 'defaultFrom',
                'data-format' => 'format', 'data-label' => 'label',
                'data-required-collection' => 'requiredCollection',
            ] as $attribute => $key) {
                if (isset($values[$attribute]) && is_string($values[$attribute])) $contract[$key] = $values[$attribute];
            }
            if (array_key_exists('data-required', $values)) $contract['required'] = true;
            if (isset($values['data-options']) && is_string($values['data-options'])) {
                $contract['options'] = json_decode($values['data-options'], true) ?? [];
            }
            $fields[$path] ??= [];
            if (! in_array($contract, $fields[$path], true)) $fields[$path][] = $contract;
        }

        foreach ($fields as $path => $contracts) {
            $fields[$path] = count($contracts) === 1 ? $contracts[0] : $contracts;
        }
        ksort($fields);

        return $fields;
    }

    /** Extract non-input display formats bound to printed values. */
    private function templateDisplayFormats(string $html): array
    {
        preg_match_all('/<[a-z][a-z0-9:-]*\\b(?=[^>]*\\bdata-print\\s*=)[^>]*>/is', $html, $elements);
        $formats = [];
        foreach ($elements[0] ?? [] as $element) {
            if (! preg_match('/\\bdata-print\\s*=\\s*(["\\\'])(.*?)\\1/is', $element, $binding)
                || ! preg_match('/\\bdata-format\\s*=\\s*(["\\\'])(.*?)\\1/is', $element, $format)) continue;
            $path = html_entity_decode($binding[2], ENT_QUOTES | ENT_HTML5);
            $value = html_entity_decode($format[2], ENT_QUOTES | ENT_HTML5);
            $formats[$path] ??= [];
            if (! in_array($value, $formats[$path], true)) $formats[$path][] = $value;
        }
        foreach ($formats as &$values) sort($values);
        unset($values);
        ksort($formats);

        return $formats;
    }
}
