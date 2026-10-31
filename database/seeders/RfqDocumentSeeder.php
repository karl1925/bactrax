<?php

namespace Database\Seeders;

use App\Models\Procurement\RfqDocument;
use Illuminate\Database\Seeder;

class RfqDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            "Latest Business/Mayor's Permit issued by the city or municipality where the principal place of business of the bidder is located;",
            'Professional License/CV (for Consulting Services)',
            'PCAB License (for Infrastructure Projects)',
            'PhilGEPS Certificate of Registration or PhilGEPS Registration Number',
        ];

        foreach ($documents as $index => $title) {
            RfqDocument::updateOrCreate(
                ['sequence' => $index + 1],
                ['title' => $title, 'is_active' => true],
            );
        }
    }
}
