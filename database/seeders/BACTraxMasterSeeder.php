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
                    'name' => 'canvasser',
                    'description' => 'Official Canvasser',
                ],
                [
                    'name' => 'budget_officer',
                    'description' => 'Budget Availability Checker',
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
            | 3. Module Permissions
            |--------------------------------------------------------------------------
            */

            $permissions = [

                // =========================================================
                // PROCUREMENT
                // =========================================================

                [
                    'name' => 'procurement.view',
                    'module' => 'procurement',
                    'description' => 'View Purchase Requests and procurement records.',
                ],
                [
                    'name' => 'procurement.add',
                    'module' => 'procurement',
                    'description' => 'Create new Purchase Requests.',
                ],
                [
                    'name' => 'procurement.edit',
                    'module' => 'procurement',
                    'description' => 'Edit Purchase Requests that are still eligible for modification.',
                ],

                // =========================================================
                // SETTINGS
                // =========================================================

                [
                    'name' => 'settings.view',
                    'module' => 'settings',
                    'description' => 'View and access system settings.',
                ],
                [
                    'name' => 'settings.update',
                    'module' => 'settings',
                    'description' => 'Alter system settings.',
                ],

                // Offices
                [
                    'name' => 'offices.view',
                    'module' => 'settings',
                    'description' => 'View and manage registered offices.',
                ],
                [
                    'name' => 'offices.add',
                    'module' => 'settings',
                    'description' => 'Add new offices to the system.',
                ],
                [
                    'name' => 'offices.edit',
                    'module' => 'settings',
                    'description' => 'Edit registered office information.',
                ],
                [
                    'name' => 'offices.toggle_active',
                    'module' => 'settings',
                    'description' => 'Edit registered office information.',
                ],
                [
                    'name' => 'offices.delete',
                    'module' => 'settings',
                    'description' => 'Delete registered offices from the system.',
                ],

                // Roles
                [
                    'name' => 'roles.view',
                    'module' => 'settings',
                    'description' => 'View system roles and their assigned privileges.',
                ],
                [
                    'name' => 'roles.add',
                    'module' => 'settings',
                    'description' => 'Create new system roles.',
                ],
                [
                    'name' => 'roles.edit',
                    'module' => 'settings',
                    'description' => 'Edit system roles and their assigned privileges.',
                ],
                [
                    'name' => 'roles.delete',
                    'module' => 'settings',
                    'description' => 'Delete system roles from the system.',
                ],

                // Procurement Modes
                [
                    'name' => 'modes.view',
                    'module' => 'settings',
                    'description' => 'View configured procurement modes.',
                ],
                [
                    'name' => 'modes.add',
                    'module' => 'settings',
                    'description' => 'Add new procurement modes and configurations.',
                ],
                [
                    'name' => 'modes.edit',
                    'module' => 'settings',
                    'description' => 'Edit procurement mode configurations.',
                ],
                [
                    'name' => 'modes.delete',
                    'module' => 'settings',
                    'description' => 'Delete procurement mode configurations.',
                ],

                // Users
                [
                    'name' => 'users.view',
                    'module' => 'settings',
                    'description' => 'View registered system users.',
                ],
                [
                    'name' => 'users.add',
                    'module' => 'settings',
                    'description' => 'Create new system user accounts.',
                ],
                [
                    'name' => 'users.edit',
                    'module' => 'settings',
                    'description' => 'Edit system user accounts and assignments.',
                ],
                [
                    'name' => 'users.delete',
                    'module' => 'settings',
                    'description' => 'Delete or deactivate system user accounts.',
                ],

                // =========================================================
                // REPORTS
                // =========================================================

                [
                    'name' => 'reports.view',
                    'module' => 'reports',
                    'description' => 'View procurement reports and generated summaries.',
                ],

            ];

            $permissionIds = [];

            foreach ($permissions as $permission) {

                DB::table('permissions')->updateOrInsert(
                    ['name' => $permission['name']],
                    [
                        'module' => $permission['module'],
                        'description' => $permission['description'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $permissionIds[$permission['name']] = DB::table('permissions')
                    ->where('name', $permission['name'])
                    ->value('id');
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Role Capabilities Mapping (Privileges & Permissions for ALL Roles)
            |--------------------------------------------------------------------------
            */

            $roleCapabilities = [
                'admin' => [
                    'privileges' => ['can_route', 'can_complete', 'can_cancel', 'can_reject', 'can_return'],
                    'permissions' => array_keys($permissionIds),
                ],
                'end_user' => [
                    'privileges' => ['can_route', 'can_cancel'],
                    'permissions' => ['procurement.view', 'procurement.add', 'procurement.edit', 'reports.view'],
                ],
                'canvasser' => [
                    'privileges' => ['can_route'],
                    'permissions' => ['procurement.view'],
                ],
                'budget_officer' => [
                    'privileges' => ['can_route', 'can_return'], // <-- Granted privileges for Budget Officer
                    'permissions' => ['procurement.view', 'reports.view'],
                ],
                'bac_secretariat' => [
                    'privileges' => ['can_route', 'can_return'],
                    'permissions' => ['procurement.view', 'procurement.edit', 'reports.view'],
                ],
                'twg_member' => [
                    'privileges' => ['can_route', 'can_return'],
                    'permissions' => ['procurement.view', 'reports.view'],
                ],
                'hope' => [
                    'privileges' => ['can_complete', 'can_return', 'can_route', 'can_reject'],
                    'permissions' => ['procurement.view', 'reports.view'],
                ],
            ];

            foreach ($roleCapabilities as $roleName => $caps) {
                if (!isset($roleIds[$roleName])) {
                    continue;
                }

                $rId = $roleIds[$roleName];

                // Sync Privileges into role_privileges
                foreach ($caps['privileges'] as $pCode) {
                    if (isset($privilegeIds[$pCode])) {
                        DB::table('role_privileges')->updateOrInsert(
                            [
                                'role_id' => $rId,
                                'privilege_id' => $privilegeIds[$pCode],
                            ],
                            []
                        );
                    }
                }

                // Sync Permissions into role_permissions
                foreach ($caps['permissions'] as $pName) {
                    if (isset($permissionIds[$pName])) {
                        DB::table('role_permissions')->updateOrInsert(
                            [
                                'role_id' => $rId,
                                'permission_id' => $permissionIds[$pName],
                            ],
                            []
                        );
                    }
                }
            }

            foreach ($roleCapabilities as $roleName => $caps) {
                if (!isset($roleIds[$roleName])) {
                    continue;
                }

                $rId = $roleIds[$roleName];

                // Sync Privileges
                foreach ($caps['privileges'] as $pCode) {
                    if (isset($privilegeIds[$pCode])) {
                        DB::table('role_privileges')->updateOrInsert(
                            [
                                'role_id' => $rId,
                                'privilege_id' => $privilegeIds[$pCode],
                            ],
                            []
                        );
                    }
                }

                // Sync Permissions
                foreach ($caps['permissions'] as $pName) {
                    if (isset($permissionIds[$pName])) {
                        DB::table('role_permissions')->updateOrInsert(
                            [
                                'role_id' => $rId,
                                'permission_id' => $permissionIds[$pName],
                            ],
                            []
                        );
                    }
                }
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
                ],
                [
                    'code' => 'SVP',
                    'name' => 'Small Value Procurement',
                    'min_abc' => 0.00,
                    'max_abc' => 2000000.00,
                    'description' => 'Small Value Procurement',
                ],
                [
                    'code' => 'DC',
                    'name' => 'Direct Contracting',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Single Source Procurement',
                ],
                [
                    'code' => 'RO',
                    'name' => 'Repeat Order',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Direct procurement from winning bidder',
                ],
                [
                    'code' => 'NPEC',
                    'name' => 'Negotiated Procurement - Emergency Cases',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Procurement due to emergency cases',
                ],
                [
                    'code' => 'NPTC',
                    'name' => 'Negotiated Procurement - Take-over of Contracts',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Rescinded or terminated contracts',
                ],
                [
                    'code' => 'NA',
                    'name' => 'Negotiated Procurement - Adjacent or Contiguous',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Adjacent or contiguous infrastructure works',
                ],
                [
                    'code' => 'SA',
                    'name' => 'Scientific, Scholarly, or Artistic Work',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Specialized scientific, scholarly, or artistic work',
                ],
                [
                    'code' => 'HT',
                    'name' => 'Highly Technical Consultants',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Highly technical consulting services',
                ],
                [
                    'code' => 'LRP',
                    'name' => 'Lease of Real Property and Venue',
                    'min_abc' => 0.00,
                    'max_abc' => null,
                    'description' => 'Lease of real property, office space, or venue',
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
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 7. User Accounts (1 Account Per Role)
            |--------------------------------------------------------------------------
            */

            $usersToSeed = [
                [
                    'email' => 'admin@dict.gov.ph',
                    'name' => 'BACTrax System Administrator',
                    'role' => 'admin',
                    'office' => 'ORD',
                ],
                [
                    'email' => 'enduser@dict.gov.ph',
                    'name' => 'Operating Unit Staff (Cagayan)',
                    'role' => 'end_user',
                    'office' => 'CPO',
                ],
                [
                    'email' => 'canvasser@dict.gov.ph',
                    'name' => 'Official Canvasser',
                    'role' => 'canvasser',
                    'office' => 'BAC_SEC',
                ],
                [
                    'email' => 'budget@dict.gov.ph',
                    'name' => 'AFD Budget Officer',
                    'role' => 'budget_officer',
                    'office' => 'AFD',
                ],
                [
                    'email' => 'bacsec@dict.gov.ph',
                    'name' => 'BAC Secretariat Head',
                    'role' => 'bac_secretariat',
                    'office' => 'BAC_SEC',
                ],
                [
                    'email' => 'twg@dict.gov.ph',
                    'name' => 'Technical Working Group Lead',
                    'role' => 'twg_member',
                    'office' => 'TOD',
                ],
                [
                    'email' => 'hope@dict.gov.ph',
                    'name' => 'Regional Director (HoPE)',
                    'role' => 'hope',
                    'office' => 'ORD',
                ],
            ];

            foreach ($usersToSeed as $userData) {
                $user = User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make('Password123!'),
                    ]
                );

                // Attach Role
                if (isset($roleIds[$userData['role']])) {
                    DB::table('user_roles')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'role_id' => $roleIds[$userData['role']],
                        ],
                        []
                    );
                }

                // Attach Primary Office Scope
                if (isset($officeIds[$userData['office']])) {
                    DB::table('office_users')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'office_id' => $officeIds[$userData['office']],
                        ],
                        [
                            'is_primary' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });

        /*
        |--------------------------------------------------------------------------
        | System Settings Initial Seed
        |--------------------------------------------------------------------------
        */
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
