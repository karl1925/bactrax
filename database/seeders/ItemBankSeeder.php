<?php

namespace Database\Seeders;

use App\Models\Procurement\ItemBankItem;
use App\Models\Procurement\ProcurementItem;
use Illuminate\Database\Seeder;

class ItemBankSeeder extends Seeder
{
    /**
     * Seed a small starter catalog with stable codes and specific descriptions.
     * Prices are intentionally blank; supplier quotes remain attached to their source bids.
     */
    public function run(): void
    {
        $priceSources = [
            'Supplier canvass',
            'Request for Information',
            'Previous procurement award',
            'PhilGEPS award notice',
            'Agency price monitoring',
        ];

        $items = [
            ['item_code' => 'CAT-ICT-001', 'category' => 'ICT Equipment', 'description' => 'Laptop Computer, Business Class, 14-inch Display, 16 GB RAM, 512 GB SSD', 'unit' => 'unit', 'specifications' => 'Business-class laptop; minimum 14-inch display, 16 GB memory, 512 GB solid-state storage, Wi-Fi, webcam, and manufacturer warranty.'],
            ['item_code' => 'CAT-ICT-002', 'category' => 'ICT Equipment', 'description' => 'Desktop Computer Set, Business Class, 16 GB RAM, 512 GB SSD', 'unit' => 'set', 'specifications' => 'Desktop system with monitor, keyboard, and mouse; minimum 16 GB memory and 512 GB solid-state storage.'],
            ['item_code' => 'CAT-ICT-003', 'category' => 'ICT Equipment', 'description' => 'Multifunction Printer, A4, Print Scan Copy', 'unit' => 'unit', 'specifications' => 'A4 multifunction printer with print, scan, and copy functions; specify ink or toner system in the procurement request.'],
            ['item_code' => 'CAT-ICT-004', 'category' => 'ICT Equipment', 'description' => 'Uninterruptible Power Supply, 1000 VA', 'unit' => 'unit', 'specifications' => 'Line-interactive UPS rated at 1000 VA or higher, with battery backup and surge protection.'],
            ['item_code' => 'CAT-NET-001', 'category' => 'Network Equipment', 'description' => 'Gigabit Ethernet Network Switch, 24-Port, Managed', 'unit' => 'unit', 'specifications' => 'Managed Layer 2 switch with 24 Gigabit Ethernet ports; uplink and management requirements to be confirmed per procurement.'],
            ['item_code' => 'CAT-NET-002', 'category' => 'Network Equipment', 'description' => 'Wireless Access Point, Wi-Fi 6, Dual Band', 'unit' => 'unit', 'specifications' => 'Dual-band Wi-Fi 6 access point with centralized management capability and power adapter or PoE support.'],
            ['item_code' => 'CAT-NET-003', 'category' => 'Network Supplies', 'description' => 'Ethernet Cable, Category 6, 305-Meter Box', 'unit' => 'box', 'specifications' => 'Category 6 UTP cable, 305-meter pull box; conductor and fire-rating requirements to be set in the procurement request.'],
            ['item_code' => 'CAT-OFF-001', 'category' => 'Office Supplies', 'description' => 'Copy Paper, A4, 80 gsm, 500 Sheets', 'unit' => 'ream', 'specifications' => 'A4 white multipurpose copy paper, 80 gsm, 500 sheets per ream.'],
            ['item_code' => 'CAT-OFF-002', 'category' => 'Office Supplies', 'description' => 'Copy Paper, Legal, 80 gsm, 500 Sheets', 'unit' => 'ream', 'specifications' => 'Legal-size white multipurpose copy paper, 80 gsm, 500 sheets per ream.'],
            ['item_code' => 'CAT-FUR-001', 'category' => 'Furniture and Fixtures', 'description' => 'Office Chair, Adjustable, Mid-Back', 'unit' => 'unit', 'specifications' => 'Office chair with adjustable seat height, mid-back support, and stable caster base; material and load rating to be set in the procurement request.'],
            ['item_code' => 'CAT-FUR-002', 'category' => 'Furniture and Fixtures', 'description' => 'Filing Cabinet, Four-Drawer, Steel', 'unit' => 'unit', 'specifications' => 'Four-drawer steel filing cabinet with drawer locks; dimensions and gauge to be confirmed per procurement.'],
            ['item_code' => 'CAT-SVC-001', 'category' => 'Printing Services', 'description' => 'Document Printing Service, Black and White, A4', 'unit' => 'page', 'specifications' => 'Black-and-white printing on A4 paper; quantity, paper provision, finishing, and delivery requirements are procurement-specific.'],
        ];

        foreach ($items as $attributes) {
            // Seed by stable catalog code and preserve any catalog edits made by staff.
            $item = ItemBankItem::withTrashed()->firstOrNew(['item_code' => $attributes['item_code']]);
            if (! $item->exists) {
                $item->fill($attributes + [
                    'reference_price' => null,
                    'price_source' => fake()->randomElement($priceSources),
                    'reference_date' => now()->toDateString(),
                    'is_active' => true,
                ]);
            }
            $item->save();

            if ($item->is_active && ! $item->trashed()) {
                $this->linkExactUnmatchedProcurementLines($item);
            }
        }
    }

    /** Link only unambiguous exact description-and-unit matches to the seeded catalog record. */
    private function linkExactUnmatchedProcurementLines(ItemBankItem $item): void
    {
        $description = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $item->description) ?? $item->description));
        $unit = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $item->unit) ?? (string) $item->unit));

        $query = ProcurementItem::query()
            ->whereNull('item_bank_item_id')
            ->where('is_lot', false)
            ->whereRaw('LOWER(TRIM(description)) = ?', [$description]);

        if ($unit === '') {
            $query->where(function ($query) {
                $query->whereNull('unit')->orWhereRaw("TRIM(unit) = ''");
            });
        } else {
            $query->whereRaw('LOWER(TRIM(unit)) = ?', [$unit]);
        }

        $query->update(['item_bank_item_id' => $item->id]);
    }
}
