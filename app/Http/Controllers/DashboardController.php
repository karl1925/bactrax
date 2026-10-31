<?php

namespace App\Http\Controllers;

use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementAttachment;
use App\Models\ProcurementMode\{Form, ProcurementMode};
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $userOfficeIds = $user->offices
            ->pluck('id')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | 1. Base Query - Office Boundary
        |--------------------------------------------------------------------------
        |
        | Central roles can see everything.
        |
        | Non-central users can see procurements when:
        | - Their office is the end-user/requesting office, OR
        | - Their role is assigned to the procurement's current phase.
        |
        */
        $query = Procurement::query()
            ->when(!$user->isCentralRole(), function ($q) use ($userOfficeIds, $user) {
                $q->where(function ($subQ) use ($userOfficeIds, $user) {
                    $subQ->whereIn(
                        'end_user_office_id',
                        $userOfficeIds
                    )
                        ->orWhereHas('currentPhase.roles', function ($roleQuery) use ($user) {
                            $roleQuery->whereIn('roles.id', $user->roles()->pluck('roles.id'))
                                ->where('procurement_mode_phase_roles.can_act', true);
                        });
                });
            });

        /*
        |--------------------------------------------------------------------------
        | 2. KPI Metrics
        |--------------------------------------------------------------------------
        */

        $totalActive = (clone $query)
            ->whereIn('status', [
                'DRAFT',
                'ROUTING',
                'RETURNED',
            ])
            ->count();

        $totalCompleted = (clone $query)
            ->where('status', 'COMPLETED')
            ->count();

        $totalReturned = (clone $query)
            ->where('status', 'RETURNED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. SLA Violations
        |--------------------------------------------------------------------------
        */

        $slaViolations = (clone $query)
            ->where('status', 'ROUTING')
            ->with([
                'currentPhase',
                'history',
            ])
            ->get()
            ->filter(function ($procurement) {

                $phaseStartDate = $procurement->history
                    ->where('phase_id', $procurement->current_phase_id)
                    ->whereIn('action', ['ROUTING_STARTED', 'ATTACHMENT_ROUTING_STARTED', 'PHASE_ADVANCED', 'RETURNED'])
                    ->sortByDesc('created_at')
                    ->first()?->created_at
                    ?? $procurement->created_at;

                if (!$phaseStartDate) {
                    return false;
                }

                $elapsedDays = $phaseStartDate->diffInDays(
                    Carbon::now()
                );

                $allowableDays =
                    $procurement->currentPhase?->allowable_days
                    ?? 7;

                return $elapsedDays > $allowableDays;
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Pending Action Register
        |--------------------------------------------------------------------------
        */

        $userRoleIds = $user->roles()->pluck('roles.id')->map(fn($id) => (int)$id)->all();
        $pendingActions = collect();
        $actionQuery = Procurement::query()->whereIn('status', ['DRAFT', 'ROUTING', 'RETURNED'])
            ->with([
                'endUserOffice', 'mode', 'creator', 'currentPhase.roles',
                'mode.phases.roles', 'mode.phases.attachments.signatories.role',
                'attachments.signatories.configuredRole', 'signatories.configuredRole',
            ]);
        if (!$user->isCentralRole()) {
            $actionQuery->where(function ($scope) use ($user, $userOfficeIds, $userRoleIds) {
                $scope->whereIn('end_user_office_id', $userOfficeIds)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('currentPhase.roles', fn($roles) => $roles->whereIn('roles.id', $userRoleIds)->where('procurement_mode_phase_roles.can_act', true))
                    ->orWhereHas('signatories.configuredRole', fn($roles) => $roles->whereIn('roles.id', $userRoleIds))
                    ->orWhereHas('attachments.signatories.configuredRole', fn($roles) => $roles->whereIn('roles.id', $userRoleIds));
            });
        }
        $actionProcurements = $actionQuery->orderByDesc('updated_at')->get();
        $prFormId = (int)Setting::get('pr_form', 0);
        $prFormId = $prFormId ?: (int)Form::where('code', 'PR')->where('status', 'published')->value('id');
        $canRoute = $this->userCanRoute($user);

        foreach ($actionProcurements as $procurement) {
            $status = strtoupper((string)$procurement->status);
            $prSubmission = $procurement->attachments->first(fn($submission) => !$submission->attachment_id && (int)$submission->form_id === $prFormId);

            if ($status === 'ROUTING' && filled($prSubmission?->file_path) && $prSubmission->workflow_status === 'ROUTING') {
                $signed = $procurement->signatories->where('is_signed', true);
                $pending = $procurement->signatories->sortBy('sequence')->first(fn($slot) => !$signed->contains(fn($row) => (int)$row->sequence === (int)$slot->sequence));
                if ($pending && ($user->isAdmin() || in_array((int)$pending->configured_role_id, $userRoleIds, true)) && ($user->isAdmin() || !$this->isEndUserRole($pending->configuredRole) || (int)$procurement->created_by === (int)$user->id)) {
                    $pendingActions->push($this->actionRow($procurement, 'Sign', 'Purchase Request', 'pr', false, $pending->configuredRole?->name ?? 'Signatory'));
                }
            }

            foreach ($procurement->mode?->phases ?? [] as $phase) {
                foreach ($phase->attachments as $config) {
                    $submission = $procurement->attachments->first(fn($row) => (int)$row->attachment_id === (int)$config->id);
                    if (!$submission || !filled($submission->file_path)) continue;
                    $configured = $config->signatories->sortBy(fn($step) => $step->sequence ?? $step->pivot?->sequence ?? $step->order ?? $step->id)->values();
                    if ($status === 'ROUTING' && $submission->workflow_status === 'ROUTING' && $configured->isNotEmpty()) {
                        $signed = $submission->signatories;
                        $pending = $configured->first(function ($step, $index) use ($signed) {
                            $sequence = (int)($step->sequence ?? $step->pivot?->sequence ?? $step->order ?? ($index + 1));
                            $roleId = (int)($step->role_id ?? $step->role?->id ?? 0);
                            return !$signed->contains(fn($row) => (int)$row->sequence === $sequence || ($roleId > 0 && (int)$row->configured_role_id === $roleId));
                        });
                        $roleId = (int)($pending?->role_id ?? $pending?->role?->id ?? 0);
                        if ($pending && ($user->isAdmin() || in_array($roleId, $userRoleIds, true)) && ($user->isAdmin() || !$this->isEndUserRole($pending->role) || (int)$procurement->created_by === (int)$user->id)) {
                            $pendingActions->push($this->actionRow($procurement, 'Sign', $config->name, (string)$config->id, false, $pending->role?->name ?? 'Signatory'));
                        }
                    }
                    $phaseCanAct = $user->canActOnPhase($phase);
                    if ($canRoute && $phaseCanAct && in_array($status, ['DRAFT', 'ROUTING', 'RETURNED'], true) && $submission->workflow_status === 'READY' && $configured->isNotEmpty() && !$submission->signatories->contains(fn($slot) => (bool)$slot->is_signed)) {
                        $pendingActions->push($this->actionRow($procurement, 'Route', $config->name, (string)$config->id));
                    }
                }
            }

            if ($status === 'DRAFT' || $status === 'RETURNED') {
                $firstPhase = $procurement->mode?->phases?->sortBy('phase_sequence')->first();
                $phase = $status === 'DRAFT' ? $firstPhase : ($procurement->currentPhase ?: $procurement->mode?->phases?->firstWhere('id', $procurement->current_phase_id));
                if ($canRoute && $prSubmission?->workflow_status === 'READY' && $procurement->signatories->isNotEmpty() && !$procurement->signatories->contains(fn($slot) => (bool)$slot->is_signed)) {
                    $pendingActions->push($this->actionRow($procurement, 'Route', 'Purchase Request', 'pr'));
                }
            }

            if ($status === 'RETURNED' && ((int)$procurement->created_by === (int)$user->id || ($procurement->currentPhase && $user->canActOnPhase($procurement->currentPhase)))) {
                $pendingActions->push($this->actionRow($procurement, 'Revise', 'Returned for revision', 'pr', true));
            }
        }
        $pendingActions = $pendingActions->unique(fn($row) => $row->procurement->id.'|'.$row->action.'|'.$row->document)->sortByDesc(fn($row) => $row->procurement->updated_at)->values();
        $pendingActionCounts = [
            'sign' => $pendingActions->where('action', 'Sign')->count(),
            'route' => $pendingActions->where('action', 'Route')->count(),
            'revise' => $pendingActions->where('action', 'Revise')->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | 5. Recent Activity
        |--------------------------------------------------------------------------
        */

        $recentProcurements = (clone $query)
            ->with([
                'endUserOffice',
                'mode',
            ])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $modes = ProcurementMode::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view('dashboard.index', compact(
            'modes',
            'totalActive',
            'totalCompleted',
            'totalReturned',
            'slaViolations',
            'pendingActions',
            'pendingActionCounts',
            'recentProcurements'
        ));
    }

    private function actionRow(Procurement $procurement, string $action, string $document, string $attachmentId, bool $openForm = false, ?string $roleName = null): object
    {
        return (object)[
            'procurement' => $procurement,
            'action' => $action,
            'document' => $document,
            'attachment_id' => $attachmentId,
            'open_form' => $openForm,
            'role_name' => $roleName,
        ];
    }

    private function userCanRoute($user): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->roles()->whereHas('permissions', fn($query) => $query->where('name', 'procurement.route'))->exists()) return true;
        return $user->roles()->whereHas('privileges', fn($query) => $query->whereIn('code', ['can_route', 'procurement.route']))->exists();
    }

    private function isEndUserRole($role): bool
    {
        return $role && collect([$role->slug, $role->code, $role->name])
            ->contains(fn($value) => strtolower(str_replace([' ', '-'], '_', (string)$value)) === 'end_user');
    }
}
