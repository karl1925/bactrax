<?php

namespace Database\Seeders;

use App\Models\Procurement\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Add realistic-looking but fictional, inactive profiles for local development and UI review.
     * These records are not real vendors and must not be treated as eligible procurement sources.
     */
    public function run(): void
    {
        $samples = [
            [
                'business_name' => 'Cagayan Valley Office Solutions (Demo)',
                'business_type' => 'Office supplies and consumables',
                'contact_person' => 'Demo Contact 01',
                'email' => 'sales@cagayan-office.example.test',
                'phone' => '+63 000 000 0101',
                'business_address' => 'Sample business address, Tuguegarao City, Cagayan',
                'notes' => 'Fictional development record. Contact details and address are placeholders; verify all supplier credentials before operational use.',
            ],
            [
                'business_name' => 'Northstar ICT Systems (Demo)',
                'business_type' => 'Information and communications technology equipment',
                'contact_person' => 'Demo Contact 02',
                'email' => 'quotations@northstar-ict.example.test',
                'phone' => '+63 000 000 0102',
                'business_address' => 'Sample business address, Santiago City, Isabela',
                'notes' => 'Fictional development record. Contact details and address are placeholders; verify all supplier credentials before operational use.',
            ],
            [
                'business_name' => 'Isabela Prime Trading (Demo)',
                'business_type' => 'General merchandise and equipment',
                'contact_person' => 'Demo Contact 03',
                'email' => 'bids@isabela-prime.example.test',
                'phone' => '+63 000 000 0103',
                'business_address' => 'Sample business address, Ilagan City, Isabela',
                'notes' => 'Fictional development record. Contact details and address are placeholders; verify all supplier credentials before operational use.',
            ],
            [
                'business_name' => 'Sierra Madre Office Furnishings (Demo)',
                'business_type' => 'Office furniture and fixtures',
                'contact_person' => 'Demo Contact 04',
                'email' => 'sales@sierra-madre-furnishings.example.test',
                'phone' => '+63 000 000 0104',
                'business_address' => 'Sample business address, Cauayan City, Isabela',
                'notes' => 'Fictional development record. Contact details and address are placeholders; verify all supplier credentials before operational use.',
            ],
            [
                'business_name' => 'Tuguegarao Print and Copy Services (Demo)',
                'business_type' => 'Printing and reprographic services',
                'contact_person' => 'Demo Contact 05',
                'email' => 'service@tuguegarao-print.example.test',
                'phone' => '+63 000 000 0105',
                'business_address' => 'Sample business address, Tuguegarao City, Cagayan',
                'notes' => 'Fictional development record. Contact details and address are placeholders; verify all supplier credentials before operational use.',
            ],
            [
                'business_name' => 'Valleywide Electrical and Industrial Supply (Demo)',
                'business_type' => 'Electrical and industrial supplies',
                'contact_person' => 'Demo Contact 06',
                'email' => 'inquiries@valleywide-industrial.example.test',
                'phone' => '+63 000 000 0106',
                'business_address' => 'Sample business address, Cauayan City, Isabela',
                'notes' => 'Fictional development record. Contact details and address are placeholders; verify all supplier credentials before operational use.',
            ],
        ];

        $legacySampleEmails = [
            'sales@cagayan-office.example.test' => 'supplier01@example.test',
            'quotations@northstar-ict.example.test' => 'supplier02@example.test',
            'bids@isabela-prime.example.test' => 'supplier03@example.test',
        ];

        foreach ($samples as $attributes) {
            $supplier = Supplier::withTrashed()->firstOrNew(['email' => $attributes['email']]);
            $isLegacySample = false;

            // Upgrade the original numbered demo rows in place when this seeder is rerun.
            if (! $supplier->exists && isset($legacySampleEmails[$attributes['email']])) {
                $supplier = Supplier::withTrashed()->firstOrNew(['email' => $legacySampleEmails[$attributes['email']]]);
                $isLegacySample = $supplier->exists;
            }

            // Preserve edits after the initial insert, except when replacing our old generic demo data.
            if (! $supplier->exists || $isLegacySample) {
                $supplier->fill($attributes + ['is_active' => false]);
            }

            if ($supplier->trashed()) {
                $supplier->restore();
            } else {
                $supplier->save();
            }
        }
    }
}
