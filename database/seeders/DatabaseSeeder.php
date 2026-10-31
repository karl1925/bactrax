<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BACTraxMasterSeeder::class,
            PermissionSeeder::class,
            FormContentSeeder::class,
            RfqDocumentSeeder::class,
            ProcurementFormSeeder::class,
            ProcurementModePhaseSeeder::class,
            FundClusterSeeder::class,
            SupplierSeeder::class,
            ItemBankSeeder::class,
        ]);
    }
}
