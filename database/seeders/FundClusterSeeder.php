<?php

namespace Database\Seeders;

use App\Models\FundCluster;
use Illuminate\Database\Seeder;

class FundClusterSeeder extends Seeder
{
    public function run(): void
    {
        FundCluster::upsert([
            [
                'name' => 'Regular Fund',
                'code' => 'REGULAR',
                'is_active' => true,
            ],
            [
                'name' => 'DigiGov',
                'code' => 'DIGIGOV',
                'is_active' => true,
            ],
            [
                'name' => 'FreeWifi',
                'code' => 'FREEWIFI',
                'is_active' => true,
            ],
        ], ['code'], ['name', 'is_active', 'updated_at']);
    }
}
