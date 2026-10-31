<?php

namespace Database\Seeders;

use App\Models\ProcurementMode\Attachment;
use App\Models\ProcurementMode\AttachmentSignatory;
use App\Models\ProcurementMode\Form;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\ProcurementMode\ProcurementModePhase;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementModePhaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()->whereIn('name', [
            'end_user',
            'canvasser',
            'procurement_unit',
            'budget_unit',
            'budget_officer',
            'accountant',
            'bac_chair',
            'bac_secretariat',
            'hope',
        ])->pluck('id', 'name');

        $requiredRoles = [
            'end_user',
            'canvasser',
            'procurement_unit',
            'budget_unit',
            'budget_officer',
            'accountant',
            'bac_chair',
            'bac_secretariat',
            'hope',];
        $missingRoles = array_values(array_diff($requiredRoles, $roles->keys()->all()));
        if ($missingRoles !== []) {
            throw new \RuntimeException('Cannot seed procurement workflow: missing roles: '.implode(', ', $missingRoles));
        }

        $mode = ProcurementMode::query()->where('code', 'SVP')->first();
        if (!$mode) {
            throw new \RuntimeException('Cannot seed procurement workflow: procurement mode SVP is missing.');
        }

        $forms = Form::query()
            ->whereIn('code', ['RFQ', 'MIN', 'ABS', 'RES'])
            ->where('status', 'published')
            ->get()
            ->keyBy('code');
        $missingForms = array_values(array_diff(['RFQ', 'MIN', 'ABS', 'RES'], $forms->keys()->all()));
        if ($missingForms !== []) {
            throw new \RuntimeException('Cannot seed SVP workflow: published forms are missing: '.implode(', ', $missingForms).'. Run ProcurementFormSeeder first.');
        }

        $phases = [
            [
                'sequence' => 1,
                'name' => 'Purchase Request Initiation and Approval',
                'description' => 'Create the purchase request, identify the funding and procurement requirements, and obtain internal approval.',
                'allowable_days' => 5,
                'roles' => ['end_user'],
                'attachments' => [
                    ['name' => 'Approved PPMP / APP reference', 'mandatory' => true],
                    ['name' => 'Technical Specifications / Terms of Reference, as applicable', 'mandatory' => true],
                    ['name' => 'Market Scoping documentation', 'mandatory' => true],
                ],
            ],
            [
                'sequence' => 2,
                'name' => 'RFQ Preparation and Solicitation',
                'description' => 'Prepare and approve the Request for Quotation, then issue it to prospective suppliers under the applicable procurement method.',
                'allowable_days' => 2,
                'roles' => ['end_user', 'bac_secretariat'],
                'attachments' => [
                    ['name' => 'Request for Quotation (RFQ)', 'mandatory' => true, 'form_code' => 'RFQ', 'signatories' => ['bac_chair']],
                    ['name' => 'Approved ABC / cost estimate /quotation deadline', 'mandatory' => false],
                    ['name' => 'RFQ posting / invitation record, if required', 'mandatory' => false],
                    ['name' => 'Evaluation and award basis (per item or per lot)', 'mandatory' => false],
                ],
            ],
            [
                'sequence' => 3,
                'name' => 'Supplier Canvassing, Evaluation, and BAC Approval',
                'description' => 'Collect supplier quotations, document the evaluation, prepare the required procurement records, and obtain the necessary approvals.',
                'allowable_days' => 2,
                'roles' => ['canvasser', 'bac_secretariat'],
                'attachments' => [
                    ['name' => 'Canvassed RFQs', 'mandatory' => true],
                    ['name' => 'Abstract of Quotations (ABS)', 'mandatory' => true, 'form_code' => 'ABS'],
                    ['name' => 'Minutes of the Meeting (MIN)', 'mandatory' => true, 'form_code' => 'MIN'],
                    ['name' => 'Resolution / recommendation (RES)', 'mandatory' => true, 'form_code' => 'RES'],
                    ['name' => 'Evaluation results and award recommendation', 'mandatory' => true],
                    ['name' => "Suppliers' eligibility documents", 'mandatory' => true],
                ],
            ],
            [
                'sequence' => 4,
                'name' => 'Purchase Order Preparation and Approval',
                'description' => 'Generate the Purchase Order using the approved procurement results and route it for authorization.',
                'allowable_days' => 2,
                'roles' => ['bac_secretariat', 'bac_chair'],
                'attachments' => [
                    ['name' => 'Purchase Order (PO)', 'mandatory' => true, 'signatories' => ['hope']],
                    ['name' => 'Supplier and award details', 'mandatory' => false],
                    ['name' => 'Item quantities, unit prices, totals, and delivery terms', 'mandatory' => false],
                    ['name' => 'Approved MIN / ABS / RES package', 'mandatory' => true],
                ],
            ],
            [
                'sequence' => 5,
                'name' => 'Supplier Acceptance and PO Signing',
                'description' => 'Transmit the approved PO to the winning supplier and obtain the required acceptance or signature.',
                'allowable_days' => 5,
                'roles' => ['end_user'],
                'attachments' => [
                    ['name' => 'Supplier acceptance / PO conforme,', 'mandatory' => true],
                ],
            ],
            [
                'sequence' => 6,
                'name' => 'ORS/BURS information',
                'description' => 'Processes the supplier-signed PO through the Budget Unit and obtains the applicable ORS/BURS number.',
                'allowable_days' => 7,
                'roles' => ['end_user', 'budget_officer'],
                'attachments' => [
                    ['name' => 'ORS/BURS information,', 'mandatory' => true, 'signatories' => ['budget_officer', 'hope']],
                ],
            ],
            [
                'sequence' => 7,
                'name' => 'COA Review, Stamping, and Coding',
                'description' => 'Submits the signed PO with its ORS/BURS number for applicable COA processing.',
                'allowable_days' => 7,
                'roles' => ['bac_secretariat'],
                'attachments' => [
                    ['name' => 'COA processing / document tracking details', 'mandatory' => true],
                ],
            ],
            [
                'sequence' => 8,
                'name' => 'Release of COA-Stamped Purchase Order',
                'description' => 'Returns the COA-stamped PO to the End User for document compilation and subsequent processing.',
                'allowable_days' => 7,
                'roles' => ['end_user', 'bac_secretariat'],
                'attachments' => [
                    ['name' => 'Document transmittal / acknowledgment', 'mandatory' => true],
                ],
            ],
            [
                'sequence' => 9,
                'name' => 'Procurement Document Compilation and Payment Processing',
                'description' => 'Compiles the complete procurement records and submits them for payment processing.',
                'allowable_days' => 7,
                'roles' => ['end_user', 'bac_secretariat'],
                'attachments' => [
                    ['name' => 'Procurement document completion checklist', 'mandatory' => true],
                    ['name' => 'Delivery and inspection/acceptance records', 'mandatory' => true],
                    ['name' => 'Invoice and Disbursement Voucher (DV) information', 'mandatory' => true],
                ],
            ],
        ];

        DB::transaction(function () use ($mode, $phases, $roles, $forms): void {
            foreach ($phases as $phaseData) {
                $phase = ProcurementModePhase::query()->updateOrCreate(
                    [
                        'procurement_mode_id' => $mode->id,
                        'phase_sequence' => $phaseData['sequence'],
                    ],
                    [
                        'phase_name' => $phaseData['name'],
                        'description' => $phaseData['description'],
                        'allowable_days' => $phaseData['allowable_days'],
                    ],
                );

                $phase->roles()->sync(collect($phaseData['roles'])
                    ->mapWithKeys(fn(string $roleName): array => [$roles[$roleName] => ['can_act' => true]])
                    ->all());

                $attachmentNames = collect($phaseData['attachments'])->pluck('name')->all();
                $phase->attachments()->whereNotIn('name', $attachmentNames)->delete();

                foreach ($phaseData['attachments'] as $attachmentData) {
                    $attachment = Attachment::query()->firstOrNew([
                        'procurement_mode_phase_id' => $phase->id,
                        'name' => $attachmentData['name'],
                    ]);
                    $attachment->fill([
                        'form_id' => isset($attachmentData['form_code'])
                            ? $forms[$attachmentData['form_code']]->id
                            : (array_key_exists('form_id', $attachmentData) ? $attachmentData['form_id'] : $attachment->form_id),
                        'is_mandatory' => $attachmentData['mandatory'],
                        'is_active' => true,
                    ])->save();

                    $attachment->signatories()->delete();
                    foreach ($attachmentData['signatories'] ?? [] as $index => $roleName) {
                        AttachmentSignatory::query()->create([
                            'attachment_id' => $attachment->id,
                            'sequence' => $index + 1,
                            'role_id' => $roles[$roleName],
                        ]);
                    }
                }
            }

            ProcurementModePhase::query()
                ->where('procurement_mode_id', $mode->id)
                ->whereNotIn('phase_sequence', collect($phases)->pluck('sequence'))
                ->delete();

        });
    }
}
