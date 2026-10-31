<?php

namespace Tests\Feature;

use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementAttachment;
use App\Models\ProcurementMode\Form;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementItemCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_pr_autosave_updates_items_and_lot_members_in_place_and_deletes_omitted_rows(): void
    {
        $this->seed();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $form = Form::query()->where('code', 'PR')->firstOrFail();
        Setting::set('pr_form', $form->id, 'procurement');
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();

        $procurement = Procurement::create([
            'purpose' => 'PR item CRUD test',
            'procurement_mode_id' => $mode->id,
            'status' => 'DRAFT',
            'created_by' => $admin->id,
        ]);
        $standalone = $procurement->items()->create([
            'line_no' => 1,
            'unit' => 'box',
            'description' => 'Old standalone',
            'quantity' => 1,
            'cost' => 10,
        ]);
        $deleted = $procurement->items()->create([
            'line_no' => 2,
            'unit' => 'pc',
            'description' => 'Remove this row',
            'quantity' => 1,
            'cost' => 1,
        ]);
        $lot = $procurement->items()->create([
            'line_no' => 3,
            'is_lot' => true,
            'unit' => 'Lot',
            'description' => 'Equipment package',
        ]);
        $member = $procurement->items()->create([
            'line_no' => 4,
            'parent_id' => $lot->id,
            'unit' => 'set',
            'description' => 'Old lot member',
            'quantity' => 2,
            'cost' => 100,
        ]);

        $fields = [
            'purpose' => 'Updated purpose saved from the PR form',
            "items[0][id]" => (string)$standalone->id,
            "items[0][unit]" => 'carton',
            "items[0][description]" => 'Updated standalone',
            "items[0][quantity]" => '3',
            "items[0][cost]" => '12.50',
            "items[1][id]" => (string)$lot->id,
            "items[1][is_lot]" => '1',
            "items[1][description]" => 'Equipment package',
            "items[1][sub_items][0][id]" => (string)$member->id,
            "items[1][sub_items][0][unit]" => '',
            "items[1][sub_items][0][description]" => 'Updated lot member',
            "items[1][sub_items][0][quantity]" => '4',
            "items[1][sub_items][0][cost]" => '25',
            "items[1][sub_items][1][unit]" => '',
            "items[1][sub_items][1][description]" => 'New lot member',
            "items[1][sub_items][1][quantity]" => '2',
            "items[1][sub_items][1][cost]" => '8',
        ];

        $this->actingAs($admin)
            ->postJson(route('procurements.attachments.save', [$procurement, 'pr']), [
                'fields' => json_encode($fields, JSON_THROW_ON_ERROR),
                'is_complete' => false,
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'procurement_item_count' => 4]);

        $this->assertDatabaseHas('procurement_items', [
            'id' => $standalone->id,
            'unit' => 'carton',
            'description' => 'Updated standalone',
            'quantity' => 3,
            'cost' => 12.50,
            'parent_id' => null,
        ]);
        $this->assertSame('Updated purpose saved from the PR form', $procurement->fresh()->purpose);
        $this->assertSame(
            'Updated purpose saved from the PR form',
            ProcurementAttachment::query()
                ->where('procurement_id', $procurement->id)
                ->whereNull('attachment_id')
                ->firstOrFail()
                ->form_data['purpose']
        );
        $this->assertDatabaseHas('procurement_items', [
            'id' => $lot->id,
            'is_lot' => true,
            'unit' => 'Lot',
            'parent_id' => null,
        ]);
        $this->assertDatabaseHas('procurement_items', [
            'id' => $member->id,
            'parent_id' => $lot->id,
            'unit' => null,
            'description' => 'Updated lot member',
            'quantity' => 4,
            'cost' => 25,
        ]);
        $this->assertDatabaseMissing('procurement_items', ['id' => $deleted->id]);
        $this->assertDatabaseHas('procurement_items', [
            'procurement_id' => $procurement->id,
            'parent_id' => $lot->id,
            'unit' => null,
            'description' => 'New lot member',
            'quantity' => 2,
            'cost' => 8,
        ]);
        $this->assertSame(153.5, (float)$procurement->fresh()->abc_amount);
    }

    public function test_pr_item_ids_cannot_be_used_across_procurements(): void
    {
        $this->seed();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $form = Form::query()->where('code', 'PR')->firstOrFail();
        Setting::set('pr_form', $form->id, 'procurement');
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $procurement = Procurement::create(['procurement_mode_id' => $mode->id, 'status' => 'DRAFT', 'created_by' => $admin->id]);
        $otherProcurement = Procurement::create(['procurement_mode_id' => $mode->id, 'status' => 'DRAFT', 'created_by' => $admin->id]);
        $foreignItem = $otherProcurement->items()->create([
            'line_no' => 1,
            'unit' => 'pc',
            'description' => 'Foreign item',
        ]);

        $fields = [
            'items[0][id]' => (string)$foreignItem->id,
            'items[0][unit]' => 'pc',
            'items[0][description]' => 'Attempted modification',
        ];

        $this->actingAs($admin)
            ->postJson(route('procurements.attachments.save', [$procurement, 'pr']), [
                'fields' => json_encode($fields, JSON_THROW_ON_ERROR),
                'is_complete' => false,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('procurement_items', [
            'id' => $foreignItem->id,
            'procurement_id' => $otherProcurement->id,
            'description' => 'Foreign item',
        ]);
    }

    public function test_a_lot_with_one_member_saves_without_a_placeholder_header_description(): void
    {
        $this->seed();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $form = Form::query()->where('code', 'PR')->firstOrFail();
        Setting::set('pr_form', $form->id, 'procurement');
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $procurement = Procurement::create([
            'procurement_mode_id' => $mode->id,
            'status' => 'DRAFT',
            'created_by' => $admin->id,
        ]);

        $fields = [
            'items[0][is_lot]' => '1',
            'items[0][description]' => '',
            'items[0][sub_items][0][description]' => 'One lot member',
            'items[0][sub_items][0][quantity]' => '2',
            'items[0][sub_items][0][cost]' => '15',
        ];

        $this->actingAs($admin)
            ->postJson(route('procurements.attachments.save', [$procurement, 'pr']), [
                'fields' => json_encode($fields, JSON_THROW_ON_ERROR),
                'is_complete' => false,
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'procurement_item_count' => 2]);

        $this->assertDatabaseHas('procurement_items', [
            'procurement_id' => $procurement->id,
            'is_lot' => true,
            'parent_id' => null,
            'description' => null,
        ]);
        $this->assertDatabaseHas('procurement_items', [
            'procurement_id' => $procurement->id,
            'parent_id' => Procurement::query()->findOrFail($procurement->id)->items()->where('is_lot', true)->value('id'),
            'description' => 'One lot member',
            'quantity' => 2,
            'cost' => 15,
        ]);
    }
}
