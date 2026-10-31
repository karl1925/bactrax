<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'procurement' => ['view' => 'View procurement records.', 'add' => 'Create procurement records.', 'edit' => 'Edit eligible procurement records.', 'modify_pr_form' => 'Modify the procurement-level Purchase Request form while the procurement is draft or returned.', 'archive' => 'Archive procurement records.', 'delete' => 'Permanently delete archived procurement records.'],
            'settings' => ['view' => 'View system settings.', 'update' => 'Change system settings.'],
            'offices' => ['view' => 'View offices.', 'add' => 'Create offices.', 'edit' => 'Edit offices.', 'toggle_active' => 'Activate or deactivate offices.', 'delete' => 'Delete offices.'],
            'roles' => ['view' => 'View roles and permissions.', 'add' => 'Create roles.', 'edit' => 'Edit roles and permissions.', 'delete' => 'Delete roles.'],
            'modes' => ['view' => 'View procurement modes.', 'add' => 'Create procurement modes.', 'edit' => 'Edit procurement modes.', 'delete' => 'Delete procurement modes.'],
            'fundclusters' => ['view' => 'View fund clusters.', 'add' => 'Create fund clusters.', 'edit' => 'Edit fund clusters.', 'delete' => 'Delete fund clusters.'],
            'users' => ['view' => 'View users.', 'add' => 'Create user accounts.', 'edit' => 'Edit user accounts and assignments.', 'delete' => 'Deactivate or delete accounts.'],
            'forms' => ['view' => 'View form templates.', 'add' => 'Create form templates.', 'edit' => 'Edit form templates.', 'delete' => 'Archive or delete form templates.'],
            'rfq_documents' => ['view' => 'View required RFQ documents.', 'add' => 'Create required RFQ documents.', 'edit' => 'Edit and activate required RFQ documents.', 'delete' => 'Delete unused RFQ documents.'],
            'reports' => ['view' => 'View procurement reports.'],
            'suppliers' => ['view' => 'View supplier profiles and documents.', 'add' => 'Create supplier profiles.', 'edit' => 'Edit supplier profiles and upload documents.', 'delete' => 'Delete supplier profiles without bid history.'],
            'items' => ['view' => 'View the reusable procurement item bank.', 'add' => 'Add items to the item bank.', 'edit' => 'Edit item bank entries.', 'delete' => 'Remove item bank entries.'],
        ];

        $ids = [];
        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action => $description) {
                $name = $module.'.'.$action;
                DB::table('permissions')->updateOrInsert(['name' => $name], [
                    'module' => $module,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $ids[$name] = DB::table('permissions')->where('name', $name)->value('id');
            }
        }

        $grants = [
            'end_user' => ['procurement.view', 'procurement.add', 'procurement.edit', 'procurement.modify_pr_form', 'reports.view'],
            'procurement_unit' => ['procurement.view', 'procurement.archive'],
            'budget_unit' => ['procurement.view'],
            'accountant' => ['procurement.view'],
            'canvasser' => ['procurement.view'],
            'budget_officer' => ['procurement.view', 'reports.view'],
            'bac_chair' => ['procurement.view', 'procurement.edit', 'reports.view'],
            'bac_secretariat' => ['procurement.view', 'procurement.edit', 'reports.view'],
            'twg_member' => ['procurement.view', 'reports.view'],
            'hope' => ['procurement.view', 'reports.view'],
        ];

        $supplierEditors = ['canvasser', 'procurement_unit', 'end_user'];
        foreach ($supplierEditors as $role) {
            $grants[$role] = array_merge($grants[$role] ?? [], [
                'suppliers.view', 'suppliers.add', 'suppliers.edit', 'suppliers.delete',
                'items.view', 'items.add', 'items.edit', 'items.delete',
            ]);
        }

        foreach ($grants as $roleName => $names) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if (!$roleId) continue;
            foreach (array_unique($names) as $name) {
                if (!isset($ids[$name])) continue;
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $ids[$name]], []
                );
            }
        }

        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        if ($adminId) {
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $adminId, 'permission_id' => $permissionId], []
                );
            }
        }
    }
}
