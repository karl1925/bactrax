<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BACTraxMasterSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1. Roles
            |--------------------------------------------------------------------------
            */

            $roles = [
                [
                    'name' => 'admin',
                    'description' => 'System Administrator',
                ],
                [
                    'name' => 'end_user',
                    'description' => 'Operating Unit',
                ],
                [
                    'name' => 'procurement_unit',
                    'description' => 'Procurement Unit',
                ],
                [
                    'name' => 'budget_unit',
                    'description' => 'Budget Unit',
                ],
                [
                    'name' => 'accountant',
                    'description' => 'Accountant',
                ],
                [
                    'name' => 'canvasser',
                    'description' => 'Official Canvasser',
                ],
                [
                    'name' => 'budget_officer',
                    'description' => 'Budget Availability Checker',
                ],
                [
                    'name' => 'bac_chair',
                    'description' => 'Bids and Awards Committee Chairperson',
                ],
                [
                    'name' => 'bac_secretariat',
                    'description' => 'Bids and Awards Committee Secretariat',
                ],
                [
                    'name' => 'twg_member',
                    'description' => 'Technical Working Group Member',
                ],
                [
                    'name' => 'hope',
                    'description' => 'Head of Procuring Entity (Regional Director)',
                ],
            ];

            $roleIds = [];

            foreach ($roles as $role) {
                DB::table('roles')->updateOrInsert(
                    ['name' => $role['name']],
                    [
                        'description' => $role['description'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $roleIds[$role['name']] = DB::table('roles')
                    ->where('name', $role['name'])
                    ->value('id');
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Action Privileges
            |--------------------------------------------------------------------------
            */

            $privileges = [

                [
                    'code' => 'can_route',
                    'name' => 'Can Route PR',
                    'description' => 'Route or forward a procurement request to the next responsible office or user for appropriate action.',
                ],
                [
                    'code' => 'can_cancel',
                    'name' => 'Can Cancel PR',
                    'description' => 'Cancel a procurement request when it is no longer required or should no longer proceed through the procurement process.',
                ],
                [
                    'code' => 'can_return',
                    'name' => 'Can Return PR',
                    'description' => 'Return a procurement request to the previous office or responsible user for correction, completion, or resolution of deficiencies.',
                ],
                [
                    'code' => 'can_complete',
                    'name' => 'Can Approve PR',
                    'description' => 'Approve the procurement request or current procurement phase, authorizing it to proceed to the next stage of the process.',
                ],
                [
                    'code' => 'can_reject',
                    'name' => 'Can Reject PR',
                    'description' => 'Reject the procurement request or current procurement phase when it does not meet the applicable requirements or should not proceed.',
                ],
                [
                    'code' => 'can_archive',
                    'name' => 'Can Archive PR',
                    'description' => 'Move a procurement request to the archive.',
                ],
                [
                    'code' => 'can_delete',
                    'name' => 'Can Delete PR',
                    'description' => 'Delete an archived procurement request and its related records.',
                ],

            ];

            $privilegeIds = [];

            foreach ($privileges as $privilege) {

                DB::table('privileges')->updateOrInsert(
                    ['code' => $privilege['code']],
                    [
                        'name' => $privilege['name'],
                        'description' => $privilege['description'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $privilegeIds[$privilege['code']] = DB::table('privileges')
                    ->where('code', $privilege['code'])
                    ->value('id');
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Role Privileges
            |--------------------------------------------------------------------------
            */

            $rolePrivileges = [
                'admin' => array_keys($privilegeIds),
                'end_user' => ['can_route', 'can_cancel'],
                'procurement_unit' => ['can_route'],
                'budget_unit' => ['can_route'],
                'accountant' => ['can_route'],
                'canvasser' => ['can_route'],
                'budget_officer' => ['can_route', 'can_return'],
                'bac_chair' => ['can_route', 'can_return'],
                'bac_secretariat' => ['can_route', 'can_return'],
                'twg_member' => ['can_route', 'can_return'],
                'hope' => ['can_complete', 'can_return', 'can_route', 'can_reject'],
            ];

            foreach ($rolePrivileges as $roleName => $codes) {
                if (!isset($roleIds[$roleName])) continue;
                foreach ($codes as $code) {
                    if (isset($privilegeIds[$code])) {
                        DB::table('role_privileges')->updateOrInsert(
                            ['role_id' => $roleIds[$roleName], 'privilege_id' => $privilegeIds[$code]], []
                        );
                    }
                }
            }

            // PR form modification is managed as the procure.modify_pr_form permission.
            $obsoletePrFormPrivilegeId = DB::table('privileges')->where('code', 'can_modify_pr_form')->value('id');
            if ($obsoletePrFormPrivilegeId) {
                DB::table('role_privileges')->where('privilege_id', $obsoletePrFormPrivilegeId)->delete();
                DB::table('privileges')->where('id', $obsoletePrFormPrivilegeId)->delete();
            }


            /*
            |--------------------------------------------------------------------------
            | 5. DICT Region 2 Office Structure
            |--------------------------------------------------------------------------
            */

            $offices = [
                [
                    'code' => 'ORD',
                    'name' => 'Office of the Regional Director',
                    'location_type' => 'REGIONAL',
                ],
                [
                    'code' => 'TOD',
                    'name' => 'Technical Operations Division',
                    'location_type' => 'REGIONAL',
                ],
                [
                    'code' => 'AFD',
                    'name' => 'Admin and Finance Division',
                    'location_type' => 'REGIONAL',
                ],
                [
                    'code' => 'BAC_SEC',
                    'name' => 'BAC Secretariat Unit',
                    'location_type' => 'REGIONAL',
                ],
                [
                    'code' => 'CPO',
                    'name' => 'Provincial Office - Cagayan',
                    'location_type' => 'PROVINCIAL',
                ],
                [
                    'code' => 'IPO1',
                    'name' => 'Provincial Office - Isabela 1',
                    'location_type' => 'PROVINCIAL',
                ],
                [
                    'code' => 'IPO2',
                    'name' => 'Provincial Office - Isabela 2',
                    'location_type' => 'PROVINCIAL',
                ],
                [
                    'code' => 'NVPO',
                    'name' => 'Provincial Office - Nueva Vizcaya',
                    'location_type' => 'PROVINCIAL',
                ],
                [
                    'code' => 'QPO',
                    'name' => 'Provincial Office - Quirino',
                    'location_type' => 'PROVINCIAL',
                ],
                [
                    'code' => 'BPO',
                    'name' => 'Provincial Office - Batanes',
                    'location_type' => 'PROVINCIAL',
                ],
            ];

            $officeIds = [];

            foreach ($offices as $office) {

                DB::table('offices')->updateOrInsert(
                    ['code' => $office['code']],
                    [
                        'name' => $office['name'],
                        'location_type' => $office['location_type'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $officeIds[$office['code']] = DB::table('offices')
                    ->where('code', $office['code'])
                    ->value('id');
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Procurement Modes
            |--------------------------------------------------------------------------
            */

            $modes = [
                [
                    'code' => 'CB',
                    'name' => 'Competitive Bidding',
                    'min_abc' => 2000000.01,
                    'max_abc' => null,
                    'description' => 'Standard Public Bidding',
                    'is_active' => false,
                ],
                [
                    'code' => 'SVP',
                    'name' => 'Small Value Procurement',
                    'min_abc' => 0.00,
                    'max_abc' => 2000000.00,
                    'description' => 'Small Value Procurement',
                    'is_active' => true,
                ],
                [
                    'code' => 'DC',
                    'name' => 'Direct Contracting',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Single Source Procurement',
                    'is_active' => false,
                ],
                [
                    'code' => 'RO',
                    'name' => 'Repeat Order',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Direct procurement from winning bidder',
                    'is_active' => false,
                ],
                [
                    'code' => 'NPEC',
                    'name' => 'Negotiated Procurement - Emergency Cases',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Procurement due to emergency cases',
                    'is_active' => false,
                ],
                [
                    'code' => 'NPTC',
                    'name' => 'Negotiated Procurement - Take-over of Contracts',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Rescinded or terminated contracts',
                    'is_active' => false,
                ],
                [
                    'code' => 'NA',
                    'name' => 'Negotiated Procurement - Adjacent or Contiguous',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Adjacent or contiguous infrastructure works',
                    'is_active' => false,
                ],
                [
                    'code' => 'SA',
                    'name' => 'Scientific, Scholarly, or Artistic Work',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Specialized scientific, scholarly, or artistic work',
                    'is_active' => false,
                ],
                [
                    'code' => 'HT',
                    'name' => 'Highly Technical Consultants',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Highly technical consulting services',
                    'is_active' => false,
                ],
                [
                    'code' => 'LRP',
                    'name' => 'Lease of Real Property and Venue',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Lease of real property, office space, or venue',
                    'is_active' => false,
                ],
            ];

            foreach ($modes as $mode) {

                DB::table('procurement_modes')->updateOrInsert(
                    ['code' => $mode['code']],
                    [
                        'name' => $mode['name'],
                        'min_abc' => $mode['min_abc'],
                        'max_abc' => $mode['max_abc'],
                        'description' => $mode['description'],
                        'is_active' => $mode['is_active'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        /*
        |--------------------------------------------------------------------------
        | System Settings Initial Seed
        |--------------------------------------------------------------------------
        */
        DB::table('settings')->updateOrInsert(
            ['key' => 'pr_form'],
            ['value' => null]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'pr_signatories'],
            ['value' => '["end_user","budget_officer","hope"]', 'group' => 'procurement', 'description' => 'Ordered PR form signatory roles', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'pr_prefix'],
            ['value' => 'PR-', 'group' => 'procurement', 'description' => 'PR Tracking Prefix', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'pr_next_number'],
            ['value' => '1', 'group' => 'procurement', 'description' => 'Next PR Sequence Number', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'pr_number_padding'],
            [
                'value' => '5',
                'group' => 'procurement',
                'description' => 'PR Zero Padding Length',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'name'],
            [
                'value' => 'Department of Information and Communications Technology',
                'group' => 'entity',
                'description' => 'Name of the institution',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'region'],
            [
                'value' => 'Regional Office 02',
                'group' => 'entity',
                'description' => 'Region name',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'address'],
            [
                'value' => '02 Bagay Road, San Gabriel, Tuguegarao City Cagayan',
                'group' => 'entity',
                'description' => 'Address of the institution',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'logo'],
            [
                'value' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAACJgAAALLCAYAAAC/hKoUAABVQGNhQlgAAFVAanVtYgAAAB5qdW1kYzJwYQARABCAAACqADibcQNjMnBhAAAAVRpqdW1iAAAAR2p1bWRjMm1hABEAEIAAAKoAOJtxA3VybjpjMnBhOmEyNjJiNGYzLTc2NTUtNDBmNC05YjllLTFiNWJhZjY1OTdmMgAAAAxDanVtYgAAAClqdW1kYzJhcwARABCAAACqADibcQNjMnBhLmFzc2VydGlvbnMAAAAJ0Wp1bWIAAAA7anVtZEDLDDK7ikidpwsq1vR/Q2kTYzJwYS5pY29uAAAAABhjMnNoE6kxFq8ybGmoYQF6905k2wAAABdiZmRiAGltYWdlL3N2Zyt4bWwAAAAJd2JpZGI8c3ZnIHdpZHRoPSI3MTYiIGhlaWdodD0iNzE2IiB2aWV3Qm94PSIwIDAgNzE2IDcxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTUwOC43NDkgMzE3LjM5OUM1MTYuNzc3IDI4Ny4zMTQgNTA4Ljk5MSAyNTMuODg0IDQ4NS4zODkgMjMwLjI4MkM0NjEuNzg4IDIwNi42ODEgNDI4LjM2IDE5OC44OTUgMzk4LjI3MyAyMDYuOTIzQzM3Ni4yMzEgMTg0LjkyOCAzNDMuMzkgMTc0Ljk1NiAzMTEuMTQ4IDE4My41OTZDMjc4LjkwNiAxOTIuMjM0IDI1NS40NSAyMTcuMjkyIDI0Ny4zNiAyNDcuMzYxQzIxNy4yOTEgMjU1LjQ1MSAxOTIuMjMzIDI3OC45MSAxODMuNTk1IDMxMS4xNDlDMTc0Ljk1NyAzNDMuMzkxIDE4NC45MjcgMzc2LjIzMiAyMDYuOTI0IDM5OC4yNzRDMTk4Ljg5NiA0MjguMzU5IDIwNi42ODMgNDYxLjc4OSAyMzAuMjg0IDQ4NS4zOTFDMjUzLjg4NSA1MDguOTkyIDI4Ny4zMTMgNTE2Ljc3OSAzMTcuNDAxIDUwOC43NUMzMzkuNDQyIDUzMC43NDUgMzcyLjI4NiA1NDAuNzE3IDQwNC41MjUgNTMyLjA3OUM0MzYuNzY3IDUyMy40NDEgNDYwLjIyMyA0OTguMzg0IDQ2OC4zMTMgNDY4LjMxNUM0OTguMzgzIDQ2MC4yMjQgNTIzLjQ0IDQzNi43NjYgNTMyLjA3OCA0MDQuNTI2QzU0MC43MTYgMzcyLjI4NSA1MzAuNzQ3IDMzOS40NDMgNTA4Ljc0OSAzMTcuNDAyVjMxNy4zOTlaTTQ3MC44OTkgMjQ0Ljc3NkM0ODYuODkyIDI2MC43NyA0OTMuNDg4IDI4Mi42MDEgNDkwLjY4NyAzMDMuNDEyTDQxNS41NzcgMjYwLjA0NkM0MTIuNDExIDI1OC4yMTggNDA4LjUwOSAyNTguMjE4IDQwNS4zNDUgMjYwLjA0NkwzMTcuNDAxIDMxMC44MlYyNzcuNTI2QzMxNy40MDEgMjc1LjE5MSAzMTguNjUyIDI3My4wMDUgMzIwLjY3NiAyNzEuODM3TDM4Ny42NDQgMjMzLjE3NEM0MTQuMTc4IDIxOC4zNTMgNDQ4LjM0NiAyMjIuMjIzIDQ3MC45MDEgMjQ0Ljc3Nkg0NzAuODk5Wk0zNTcuODM3IDMxMS4xNDRMMzk4LjI3NSAzMzQuNDkxVjM4MS4xODVMMzU3LjgzNyA0MDQuNTMyTDMxNy4zOTggMzgxLjE4NVYzMzQuNDkxTDM1Ny44MzcgMzExLjE0NFpNMjY0Ljc3NiAyNjkuNjkzQzI2NS4yMDcgMjM5LjMwNSAyODUuNjQ0IDIxMS42NDkgMzE2LjQ1MyAyMDMuMzkzQzMzOC4zIDE5Ny41NCAzNjAuNTA1IDIwMi43NDQgMzc3LjEyNyAyMTUuNTczTDMwMi4wMTQgMjU4LjkzN0MyOTguODQ4IDI2MC43NjQgMjk2Ljg5OCAyNjQuMTQ0IDI5Ni44OTggMjY3Ljc5OFYzNjkuMzQ2TDI2OC4wNjUgMzUyLjY5OUMyNjYuMDQzIDM1MS41MzEgMjY0Ljc3NiAzNDkuMzUzIDI2NC43NzYgMzQ3LjAxN1YyNjkuNjkxVjI2OS42OTNaTTIwMy4zOTEgMzE2LjQ1NEMyMDkuMjQ0IDI5NC42MDggMjI0Ljg1NCAyNzcuOTc4IDI0NC4yNzYgMjY5Ljk5OVYzNTYuNzNDMjQ0LjI3NiAzNjAuMzg0IDI0Ni4yMjYgMzYzLjc2MyAyNDkuMzkyIDM2NS41OTFMMzM3LjMzNyA0MTYuMzY1TDMwOC41MDMgNDMzLjAxM0MzMDYuNDgxIDQzNC4xODEgMzAzLjk2MSA0MzQuMTg4IDMwMS45MzkgNDMzLjAyTDIzNC45NzEgMzk0LjM1N0MyMDguODY4IDM3OC43ODkgMTk1LjEzOCAzNDcuMjYxIDIwMy4zOTEgMzE2LjQ1NFpNMjQ0Ljc3NSA0NzAuOUMyMjguNzgxIDQ1NC45MDYgMjIyLjE4NiA0MzMuMDc1IDIyNC45ODYgNDEyLjI2NEwzMDAuMDk2IDQ1NS42M0MzMDMuMjYzIDQ1Ny40NTcgMzA3LjE2NCA0NTcuNDU3IDMxMC4zMjggNDU1LjYzTDM5OC4yNzMgNDA0Ljg1NlY0MzguMTQ5QzM5OC4yNzMgNDQwLjQ4NSAzOTcuMDIyIDQ0Mi42NzEgMzk0Ljk5NyA0NDMuODM5TDMyOC4wMjkgNDgyLjUwMkMzMDEuNDk1IDQ5Ny4zMjIgMjY3LjMyNyA0OTMuNDUyIDI0NC43NzIgNDcwLjlIMjQ0Ljc3NVpNNDUwLjg5NyA0NDUuOTgyQzQ1MC40NjYgNDc2LjM3MSA0MzAuMDI5IDUwNC4wMjcgMzk5LjIyIDUxMi4yODNDMzc3LjM3MyA1MTguMTM2IDM1NS4xNjggNTEyLjkzMiAzMzguNTQ3IDUwMC4xMDJMNDEzLjY1OSA0NTYuNzM4QzQxNi44MjYgNDU0LjkxMSA0MTguNzc1IDQ1MS41MzIgNDE4Ljc3NSA0NDcuODc3VjM0Ni4zMjlMNDQ3LjYwOSAzNjIuOTc3QzQ0OS42MzEgMzY0LjE0NSA0NTAuODk3IDM2Ni4zMjMgNDUwLjg5NyAzNjguNjU5VjQ0NS45ODVWNDQ1Ljk4MlpNNTEyLjI4MiAzOTkuMjIxQzUwNi40MjkgNDIxLjA2OCA0OTAuODE5IDQzNy42OTcgNDcxLjM5NyA0NDUuNjc2VjM1OC45NDZDNDcxLjM5NyAzNTUuMjkyIDQ2OS40NDggMzUxLjkxMiA0NjYuMjgxIDM1MC4wODVMMzc4LjMzNiAyOTkuMzExTDQwNy4xNyAyODIuNjYzQzQwOS4xOTIgMjgxLjQ5NSA0MTEuNzEyIDI4MS40ODcgNDEzLjczNCAyODIuNjU1TDQ4MC43MDIgMzIxLjMxOEM1MDYuODA1IDMzNi44ODcgNTIwLjUzNiAzNjguNDE1IDUxMi4yODIgMzk5LjIyMVoiIGZpbGw9ImJsYWNrIi8+Cjwvc3ZnPgoAAAF+anVtYg==',
                'group' => 'entity',
                'description' => 'Logo of the institution',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('BACTrax master data & role accounts seeded successfully.');
        $this->command->info('All accounts use default password: Password123!');
        $this->command->table(
            ['Role Key', 'User Email', 'Primary Office Scope'],
            [
                ['admin', 'admin@dict.gov.ph', 'ORD'],
                ['end_user', 'enduser@dict.gov.ph', 'PFO_CAGAYAN'],
                ['canvasser', 'canvasser@dict.gov.ph', 'BAC_SEC'],
                ['budget_officer', 'budget@dict.gov.ph', 'AFD'],
                ['bac_secretariat', 'bacsec@dict.gov.ph', 'BAC_SEC'],
                ['twg_member', 'twg@dict.gov.ph', 'TOD'],
                ['hope', 'hope@dict.gov.ph', 'ORD'],
            ]
        );
    }
}
