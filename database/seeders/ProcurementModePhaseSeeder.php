<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcurementMode;
use App\Models\ProcurementModePhase;
use App\Models\ProcurementModePhaseDocument;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ProcurementModePhaseSeeder extends Seeder
{
    public function run(): void
    {
        // Get system roles for phase access mapping
        $roleEndUser = Role::where('name', 'end_user')->first()?->id ?? 2;
        $roleCanvasser = Role::where('name', 'canvasser')->first()?->id ?? 3;
        $roleBudget = Role::where('name', 'budget_officer')->first()?->id ?? 4;
        $roleBacSec = Role::where('name', 'bac_secretariat')->first()?->id ?? 5;
        $roleTwg = Role::where('name', 'twg_member')->first()?->id ?? 6;
        $roleHope = Role::where('name', 'hope')->first()?->id ?? 7;

        DB::transaction(function () use ($roleEndUser, $roleCanvasser, $roleBudget, $roleBacSec, $roleTwg, $roleHope) {

            /* =========================================================================
            * 1. COMPETITIVE BIDDING - RA 12009 Standard Modality (Max 60-Day Cap)
            * ========================================================================= */
            $bidding = ProcurementMode::where('code', 'COMPETITIVE_BIDDING')->first();
            if ($bidding) {
                $phasesBidding = [
                    [
                        'sequence' => 1,
                        'name' => 'Strategic Procurement Planning & Pre-Procurement',
                        'allowable_days' => 5,
                        'description' => 'End-user & BAC conduct market scoping, specs definition, and pre-procurement conference.',
                        'roles' => [$roleEndUser, $roleBacSec],
                        'docs' => [
                            ['name' => 'Purchase Request', 'mandatory' => true],
                            ['name' => 'Bidding Documents Draft', 'mandatory' => true],
                            ['name' => 'Pre-Procurement Conference Minutes', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 2,
                        'name' => 'Invitation to Bid & Pre-Bid Conference',
                        'allowable_days' => 12,
                        'description' => 'PhilGEPS Invitation Posting and mandatory video-recorded/livestreamed Pre-Bid Conference.',
                        'roles' => [$roleBacSec],
                        'docs' => [
                            ['name' => 'Invitation to Bid (ITB)', 'mandatory' => true],
                            ['name' => 'PhilGEPS Posting Certificate', 'mandatory' => true],
                            ['name' => 'Pre-Bid Conference Video Log / Minutes', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'Bid Submission & Livestreamed Opening',
                        'allowable_days' => 5,
                        'description' => 'Public preliminary bid examination, PhilGEPS Platinum check, and livestreamed opening.',
                        'roles' => [$roleBacSec, $roleTwg],
                        'docs' => [
                            ['name' => 'Abstract of Bids as Read', 'mandatory' => true],
                            ['name' => 'PhilGEPS Platinum Membership Certificate', 'mandatory' => true],
                            ['name' => 'Bid Opening Video Recording Log', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 4,
                        'name' => 'Detailed Evaluation & Post-Qualification',
                        'allowable_days' => 10,
                        'description' => 'MEARB/MARB evaluation, TWG post-qualification review, and beneficial ownership verification.',
                        'roles' => [$roleTwg],
                        'docs' => [
                            ['name' => 'Detailed Bid Evaluation Report', 'mandatory' => true],
                            ['name' => 'TWG Post-Qualification Report', 'mandatory' => true],
                            ['name' => 'Beneficial Ownership Declaration', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 5,
                        'name' => 'BAC Resolution & HoPE Award Approval',
                        'allowable_days' => 5,
                        'description' => 'BAC Resolution declaring LCRB/MEARB and HoPE approval of Notice of Award.',
                        'roles' => [$roleBacSec, $roleHope],
                        'docs' => [
                            ['name' => 'BAC Resolution Declaring MEARB/LCRB', 'mandatory' => true],
                            ['name' => 'Notice of Award (NOA)', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 6,
                        'name' => 'Contract Execution & Notice to Proceed',
                        'allowable_days' => 5,
                        'description' => 'Contract signing, performance security submission, and Notice to Proceed issuance.',
                        'roles' => [$roleBacSec, $roleHope],
                        'docs' => [
                            ['name' => 'Signed Procurement Contract', 'mandatory' => true],
                            ['name' => 'Performance Security', 'mandatory' => true],
                            ['name' => 'Notice to Proceed (NTP)', 'mandatory' => true],
                        ],
                    ],
                ];
                $this->seedModePhases($bidding, $phasesBidding);
            }

            /* =========================================================================
            * 2. SMALL VALUE PROCUREMENT (SVP) - Section 53.9 / Fit-for-Purpose Modality
            * ========================================================================= */
            $svp = ProcurementMode::where('code', 'SVP')->first();
            if ($svp) {
                $phasesSvp = [
                    [
                        'sequence' => 1,
                        'name' => 'PR Preparation & Strategic Market Scoping',
                        'allowable_days' => 5,
                        'description' => 'End-user prepares Purchase Request, Market Scoping Report, and Technical Specifications / TOR.',
                        'roles' => [$roleEndUser],
                        'docs' => [
                            ['name' => 'Purchase Request (Signed by End-User)', 'mandatory' => true],
                            ['name' => 'Approved PPMP / SPPMP', 'mandatory' => true],
                            ['name' => 'Technical Specifications / TOR / POI', 'mandatory' => true],
                            ['name' => 'Market Scoping Assessment Report', 'mandatory' => false],
                        ],
                    ],
                    [
                        'sequence' => 2,
                        'name' => 'PR Validation and Budget Availability Approval',
                        'allowable_days' => 2,
                        'description' => 'Budget Officer checks PR against PPMP/SPPMP and marks PR for Funds Availability.',
                        'roles' => [$roleBudget],
                        'docs' => [
                            ['name' => 'Purchase Request (Signed by Budget Officer)', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'PR Approval',
                        'allowable_days' => 2,
                        'description' => 'Hope approval/rejection of PR as provided by law.',
                        'roles' => [$roleHope],
                        'docs' => [
                            ['name' => 'Approved Purchase Request', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 4,
                        'name' => 'Preparation of RFQ Canvass & PhilGEPS Posting',
                        'allowable_days' => 2,
                        'description' => 'BAC Secretariat prepares Requests for Quotation (RFQ) and posts canvass on PhilGEPS/Portal (if needed).',
                        'roles' => [$roleBacSec],
                        'docs' => [
                            ['name' => 'Request for Quotations (RFQ) (Signed by BAC Chair)', 'mandatory' => true],
                            ['name' => 'PhilGEPS Canvass Posting Certificate', 'mandatory' => false],
                        ],
                    ],
                    [
                        'sequence' => 5,
                        'name' => 'RFQ Canvassing',
                        'allowable_days' => 5,
                        'description' => 'Official Canvasser floats RFQ to suppliers and collects all pertinent documents thereafter.',
                        'roles' => [$roleCanvasser],
                        'docs' => [
                            ['name' => 'Copleted Request for Quotations (RFQ)', 'mandatory' => true],
                            ['name' => 'Mayor\'s / Business Permit', 'mandatory' => true],
                            ['name' => 'PhilGEPS Platinum / Red Certificate', 'mandatory' => true],
                            ['name' => 'Notarized Omnibus Sworn Statement (OSS)', 'mandatory' => false],
                        ],
                    ],
                    [
                        'sequence' => 6,
                        'name' => 'Quotation Opening & Abstract Evaluation',
                        'allowable_days' => 3,
                        'description' => 'Opening of sealed quotations, TWG evaluation, and eligibility verification.',
                        'roles' => [$roleTwg, $roleBacSec],
                        'docs' => [
                            ['name' => 'Abstract of Quotations', 'mandatory' => true],

                        ],
                    ],
                    [
                        'sequence' => 7,
                        'name' => 'BAC Resolution & Purchase Order Award',
                        'allowable_days' => 3,
                        'description' => 'BAC prepares recommendation for award to HoPE for approval and PO / NOA issuance.',
                        'roles' => [$roleBacSec],
                        'docs' => [
                            ['name' => 'BAC Resolution Recommending Award', 'mandatory' => true],
                            ['name' => 'Notice of Award (NOA)', 'mandatory' => true],
                            ['name' => 'Purchase Order (PO)', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 8,
                        'name' => 'Approval and Completion',
                        'allowable_days' => 5,
                        'description' => 'Hope\'s Approval of NOA and PO',
                        'roles' => [$roleBacSec],
                        'docs' => [
                            ['name' => 'Notice of Award (NOA)', 'mandatory' => true],
                            ['name' => 'Purchase Order (PO)', 'mandatory' => true],
                        ],
                    ],
                ];
                $this->seedModePhases($svp, $phasesSvp);
            }
            /* =========================================================================
            * 3. DIRECT CONTRACTING - Single Source Procurement
            * ========================================================================= */
            $direct = ProcurementMode::where('code', 'DIRECT_CONTRACTING')->first();
            if ($direct) {
                $phasesDirect = [
                    [
                        'sequence' => 1,
                        'name' => 'PR & Exclusive Distributorship Justification',
                        'allowable_days' => 2,
                        'description' => 'Technical evaluation justifying proprietary source or exclusive distribution.',
                        'roles' => [$roleEndUser],
                        'docs' => [
                            ['name' => 'Approved Purchase Request', 'mandatory' => true],
                            ['name' => 'Exclusive Distributorship Certification', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 2,
                        'name' => 'Direct Negotiation & Price Verification',
                        'allowable_days' => 3,
                        'description' => 'Price verification and negotiation with direct supplier.',
                        'roles' => [$roleTwg, $roleBacSec],
                        'docs' => [
                            ['name' => 'Direct Price Quotation / Offer', 'mandatory' => true],
                            ['name' => 'TWG Price Justification Report', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'Award Approval & Contract Signing',
                        'allowable_days' => 3,
                        'description' => 'HoPE approval of BAC Resolution and contract signing.',
                        'roles' => [$roleBacSec, $roleHope],
                        'docs' => [
                            ['name' => 'BAC Resolution Recommending Direct Contracting', 'mandatory' => true],
                            ['name' => 'Notice of Award (NOA)', 'mandatory' => true],
                            ['name' => 'Signed Contract / PO', 'mandatory' => true],
                        ],
                    ],
                ];
                $this->seedModePhases($direct, $phasesDirect);
            }
            /* =========================================================================
            * 4. NEGOTIATED EMERGENCY PROCUREMENT
            * ========================================================================= */
            $emergency = ProcurementMode::where('code', 'NEGOTIATED_EMERGENCY')->first();
            if ($emergency) {
                $phasesEmergency = [
                    [
                        'sequence' => 1,
                        'name' => 'Emergency PR & Disaster Justification',
                        'allowable_days' => 1,
                        'description' => 'Immediate engagement due to fortuitous events or imminent danger to life/property.',
                        'roles' => [$roleEndUser],
                        'docs' => [
                            ['name' => 'Approved Emergency Purchase Request', 'mandatory' => true],
                            ['name' => 'Disaster / Emergency Justification Memorandum', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 2,
                        'name' => 'Immediate Direct Negotiation & Award',
                        'allowable_days' => 2,
                        'description' => 'Direct price negotiation and emergency purchase order execution.',
                        'roles' => [$roleBacSec, $roleHope],
                        'docs' => [
                            ['name' => 'Emergency Price Offer', 'mandatory' => true],
                            ['name' => 'Emergency BAC Resolution', 'mandatory' => true],
                            ['name' => 'Emergency Contract / Purchase Order', 'mandatory' => true],
                        ],
                    ],
                ];
                $this->seedModePhases($emergency, $phasesEmergency);
            }
            /* =========================================================================
            * 5. LEASE OF REAL PROPERTY AND VENUE
            * ========================================================================= */
            $lease = ProcurementMode::where('code', 'LEASE_REAL_PROPERTY')->first();
            if ($lease) {
                $phasesLease = [
                    [
                        'sequence' => 1,
                        'name' => 'PR & Location Rating Factor Computation',
                        'allowable_days' => 2,
                        'description' => 'End-user prepares PR and location factor assessment (accessibility, facilities, cost).',
                        'roles' => [$roleEndUser],
                        'docs' => [
                            ['name' => 'Approved Purchase Request', 'mandatory' => true],
                            ['name' => 'Real Property / Venue Rating Factor Table', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 2,
                        'name' => 'RFQ Canvass & Rating Evaluation',
                        'allowable_days' => 4,
                        'description' => 'Canvassing venues/properties and evaluating compliance against location factor rating.',
                        'roles' => [$roleBacSec, $roleTwg],
                        'docs' => [
                            ['name' => 'Request for Quotations (RFQ)', 'mandatory' => true],
                            ['name' => 'Abstract of Rating Evaluations', 'mandatory' => true],
                        ],
                    ],
                    [
                        'sequence' => 3,
                        'name' => 'Award Approval & Lease Contract Signing',
                        'allowable_days' => 3,
                        'description' => 'HoPE approval and execution of Lease Contract or Event Order.',
                        'roles' => [$roleBacSec, $roleHope],
                        'docs' => [
                            ['name' => 'BAC Resolution Recommending Lease Award', 'mandatory' => true],
                            ['name' => 'Notice of Award (NOA)', 'mandatory' => true],
                            ['name' => 'Signed Lease Contract / Venue Agreement', 'mandatory' => true],
                        ],
                    ],
                ];
                $this->seedModePhases($lease, $phasesLease);
            }
        });
    }

    /**
     * Helper to seed dynamic phases, role permissions, and mandatory document requirements.
     */
    private function seedModePhases(ProcurementMode $mode, array $phasesData): void
    {
        foreach ($phasesData as $pData) {
            $phase = ProcurementModePhase::updateOrCreate(
                [
                    'procurement_mode_id' => $mode->id,
                    'phase_sequence' => $pData['sequence'],
                ],
                [
                    'phase_name' => $pData['name'],
                    'allowable_days' => $pData['allowable_days'],
                    'description' => $pData['description'],
                ]
            );

            // Sync Authorized Roles for Phase
            if (!empty($pData['roles'])) {
                $phase->roles()->syncWithPivotValues($pData['roles'], ['can_act' => true]);
            }

            // Sync Mandatory Document Requirements for Phase
            if (!empty($pData['docs'])) {
                foreach ($pData['docs'] as $dData) {
                    ProcurementModePhaseDocument::updateOrCreate(
                        [
                            'phase_id' => $phase->id,
                            'document_type_name' => $dData['name'],
                        ],
                        [
                            'is_mandatory' => $dData['mandatory'],
                        ]
                    );
                }
            }
        }
    }
}
