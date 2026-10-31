<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementAttachment;
use App\Models\Procurement\ProcurementAttachmentSignatory;
use App\Models\Procurement\ProcurementSignatory;
use App\Models\ProcurementMode\Form;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SvpWorkflowSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_as_the_pending_pr_role_and_sees_the_signing_turn(): void
    {
        $this->seed();
        Storage::fake('procurements');
        Http::fake([
            'https://digisign.dictr2.cloud/digicheck' => Http::response([
                'registered' => true,
                'p12_uploaded' => true,
                'require_pw' => false,
            ]),
            'https://digisign.dictr2.cloud/digisign' => Http::response('%PDF-1.4 simulated signed document', 200, [
                'Content-Type' => 'application/pdf',
                'X-PDF-Fields-Success' => 'true',
                'X-PDF-Fields-Count' => '1',
            ]),
        ]);

        $admin = User::factory()->create(['name' => 'PR signing administrator']);
        $admin->roles()->attach($this->roleId('admin'));
        $creator = User::factory()->create(['name' => 'PR creator']);
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $firstPhase = $mode->phases()->orderBy('phase_sequence')->firstOrFail();
        $prForm = Form::query()->where('code', 'PR')->firstOrFail();
        Setting::set('pr_form', $prForm->id, 'procurement');

        $procurement = Procurement::create([
            'purpose' => 'Administrator signing turn test',
            'procurement_mode_id' => $mode->id,
            'current_phase_id' => $firstPhase->id,
            'status' => 'ROUTING',
            'created_by' => $creator->id,
        ]);
        ProcurementSignatory::create([
            'procurement_id' => $procurement->id,
            'sequence' => 1,
            'configured_role_id' => $this->roleId('end_user'),
        ]);
        $submission = $this->submission($procurement, null, null, $prForm->id, 'ROUTING', true);

        $this->actingAs($admin)
            ->get(route('procurements.show', $procurement))
            ->assertOk()
            ->assertSee('Your turn to sign');

        $this->actingAs($admin)
            ->getJson(route('procurements.attachments.signing-status', [$procurement, 'pr']))
            ->assertOk()
            ->assertJsonPath('has_turn', true)
            ->assertJsonPath('can_sign', true);

        $this->sign($procurement, 'pr', $admin);
        $this->assertDatabaseHas('procurement_signatories', [
            'procurement_id' => $procurement->id,
            'sequence' => 1,
            'signatory_id' => $admin->id,
            'is_signed' => true,
        ]);
        $this->assertDatabaseHas('procurement_attachments', [
            'id' => $submission->id,
            'workflow_status' => 'COMPLETED',
        ]);
    }

    public function test_svp_procurement_can_complete_each_phase_and_signature_turn(): void
    {
        $this->seed();
        Storage::fake('procurements');
        Http::fake([
            'https://digisign.dictr2.cloud/digicheck' => Http::response([
                'registered' => true,
                'p12_uploaded' => true,
                'require_pw' => false,
            ]),
            'https://digisign.dictr2.cloud/digisign' => Http::response('%PDF-1.4 simulated signed document', 200, [
                'Content-Type' => 'application/pdf',
                'X-PDF-Fields-Success' => 'true',
                'X-PDF-Fields-Count' => '1',
            ]),
        ]);

        $users = [];
        foreach (['end_user', 'budget_officer', 'hope', 'bac_secretariat', 'bac_chair', 'canvasser', 'accountant', 'budget_unit'] as $roleName) {
            $user = User::create([
                'name' => str($roleName)->replace('_', ' ')->title(),
                'email' => $roleName.'@simulation.test',
            ]);
            $user->roles()->attach(DB::table('roles')->where('name', $roleName)->value('id'));
            $users[$roleName] = $user;
        }

        $office = Office::query()->firstOrFail();
        $users['end_user']->offices()->attach($office->id, ['is_primary' => true]);
        $mode = ProcurementMode::query()->where('code', 'SVP')->firstOrFail();
        $phases = $mode->phases()->with(['roles', 'attachments.signatories'])->orderBy('phase_sequence')->get()->values();
        $this->assertCount(9, $phases);

        $prForm = Form::query()->where('code', 'PR')->firstOrFail();
        Setting::set('pr_form', $prForm->id, 'procurement');
        Setting::set('pr_signatories', '["end_user","budget_officer","hope"]', 'procurement');

        $procurement = Procurement::create([
            'purpose' => 'SVP end-to-end workflow simulation',
            'fund_cluster' => 'General Fund',
            'responsibility_code' => 'SIM-001',
            'pr_date' => now()->toDateString(),
            'end_user_office_id' => $office->id,
            'procurement_mode_id' => $mode->id,
            'current_phase_id' => $phases[0]->id,
            'abc_amount' => 15000,
            'status' => 'DRAFT',
            'created_by' => $users['end_user']->id,
        ]);

        foreach (['end_user', 'budget_officer', 'hope'] as $index => $roleName) {
            ProcurementSignatory::create([
                'procurement_id' => $procurement->id,
                'sequence' => $index + 1,
                'configured_role_id' => DB::table('roles')->where('name', $roleName)->value('id'),
            ]);
        }

        $prSubmission = $this->submission($procurement, null, null, $prForm->id, 'READY', true);
        $this->actingAs($users['end_user'])
            ->post(route('procurements.attachments.route', [$procurement, 'pr']))
            ->assertRedirect();
        $this->assertDatabaseHas('procurements', ['id' => $procurement->id, 'status' => 'ROUTING']);
        $this->sign($procurement, 'pr', $users['end_user']);
        $this->sign($procurement, 'pr', $users['budget_officer']);
        $this->sign($procurement, 'pr', $users['hope']);
        $this->assertDatabaseHas('procurement_attachments', ['id' => $prSubmission->id, 'workflow_status' => 'COMPLETED']);

        foreach ($phases[0]->attachments as $attachment) $this->submission($procurement, $phases[0]->id, $attachment->id);
        $this->proceed($procurement, $users['end_user'], $phases[1]->id);

        $rfq = $phases[1]->attachments->firstWhere('name', 'Request for Quotation (RFQ)');
        $rfqSubmission = $this->submission($procurement, $phases[1]->id, $rfq->id, $rfq->form_id, 'READY', true, [$this->roleId('bac_chair')]);
        $this->actingAs($users['bac_secretariat'])
            ->post(route('procurements.attachments.route', [$procurement, (string) $rfq->id]))
            ->assertRedirect();
        $this->sign($procurement, (string) $rfq->id, $users['bac_chair']);
        $this->assertDatabaseHas('procurement_attachments', ['id' => $rfqSubmission->id, 'workflow_status' => 'COMPLETED']);
        $this->proceed($procurement, $users['bac_secretariat'], $phases[2]->id);

        foreach ($phases[2]->attachments as $attachment) {
            $this->submission($procurement, $phases[2]->id, $attachment->id, $attachment->form_id);
        }
        $this->proceed($procurement, $users['canvasser'], $phases[3]->id);

        $po = $phases[3]->attachments->firstWhere('name', 'Purchase Order (PO)');
        $poSubmission = $this->submission($procurement, $phases[3]->id, $po->id, null, 'READY', true, [$this->roleId('hope')]);
        foreach ($phases[3]->attachments->where('id', '!=', $po->id) as $attachment) {
            $this->submission($procurement, $phases[3]->id, $attachment->id);
        }
        $this->actingAs($users['bac_secretariat'])
            ->post(route('procurements.attachments.route', [$procurement, (string) $po->id]))
            ->assertRedirect();
        $this->sign($procurement, (string) $po->id, $users['hope']);
        $this->assertDatabaseHas('procurement_attachments', ['id' => $poSubmission->id, 'workflow_status' => 'COMPLETED']);
        $this->proceed($procurement, $users['bac_secretariat'], $phases[4]->id);

        foreach ($phases[4]->attachments as $attachment) $this->submission($procurement, $phases[4]->id, $attachment->id);
        $this->proceed($procurement, $users['end_user'], $phases[5]->id);

        $ors = $phases[5]->attachments->first();
        $orsSubmission = $this->submission($procurement, $phases[5]->id, $ors->id, null, 'READY', true, [
            $this->roleId('budget_officer'),
            $this->roleId('hope'),
        ]);
        $this->actingAs($users['end_user'])
            ->post(route('procurements.attachments.route', [$procurement, (string) $ors->id]))
            ->assertRedirect();
        $this->sign($procurement, (string) $ors->id, $users['budget_officer']);
        $this->sign($procurement, (string) $ors->id, $users['hope']);
        $this->assertDatabaseHas('procurement_attachments', ['id' => $orsSubmission->id, 'workflow_status' => 'COMPLETED']);
        $this->proceed($procurement, $users['budget_officer'], $phases[6]->id);

        foreach ($phases[6]->attachments as $attachment) $this->submission($procurement, $phases[6]->id, $attachment->id);
        $this->proceed($procurement, $users['bac_secretariat'], $phases[7]->id);

        foreach ($phases[7]->attachments as $attachment) $this->submission($procurement, $phases[7]->id, $attachment->id);
        $this->proceed($procurement, $users['end_user'], $phases[8]->id);

        foreach ($phases[8]->attachments as $attachment) $this->submission($procurement, $phases[8]->id, $attachment->id);
        $this->proceed($procurement, $users['accountant'], $phases[8]->id);

        $this->assertDatabaseHas('procurements', ['id' => $procurement->id, 'status' => 'COMPLETED']);
        $this->assertDatabaseCount('procurement_histories', 20);
    }

    private function submission(Procurement $procurement, ?int $phaseId, ?int $attachmentId, ?int $formId = null, string $status = 'COMPLETED', bool $signed = false, array $signatoryRoles = []): ProcurementAttachment
    {
        $path = "procurements/{$procurement->id}/attachments/sim-".($attachmentId ?? 'pr').'-'.uniqid().'.pdf';
        Storage::disk('procurements')->put($path, '%PDF-1.4 simulation');
        $submission = ProcurementAttachment::create([
            'procurement_id' => $procurement->id,
            'phase_id' => $phaseId,
            'attachment_id' => $attachmentId,
            'form_id' => $formId,
            'is_completed' => true,
            'workflow_status' => $status,
            'file_name' => 'simulation.pdf',
            'file_path' => $path,
            'file_size' => strlen('%PDF-1.4 simulation'),
            'mime_type' => 'application/pdf',
        ]);
        foreach ($signatoryRoles as $index => $roleId) {
            ProcurementAttachmentSignatory::create([
                'procurement_attachments_id' => $submission->id,
                'sequence' => $index + 1,
                'configured_role_id' => $roleId,
            ]);
        }
        return $submission;
    }

    private function sign(Procurement $procurement, string $attachmentId, User $user): void
    {
        $this->actingAs($user)
            ->postJson(route('procurements.attachments.sign', [$procurement, $attachmentId]), [
                'page' => 1,
                'left' => 10,
                'top' => 10,
                'width' => 120,
                'height' => 40,
                'page_width' => 595,
                'page_height' => 842,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    private function proceed(Procurement $procurement, User $user, int $expectedPhaseId): void
    {
        $this->actingAs($user)
            ->post(route('procurements.proceed-next-phase', $procurement))
            ->assertRedirect();
        $procurement->refresh();
        $this->assertSame($expectedPhaseId, (int) $procurement->current_phase_id);
    }

    private function roleId(string $role): int
    {
        return (int) DB::table('roles')->where('name', $role)->value('id');
    }
}
