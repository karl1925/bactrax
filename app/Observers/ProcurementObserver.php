<?php

namespace App\Observers;

use App\Models\Procurement\Procurement;
use App\Services\ProcurementEmailNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ProcurementObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Procurement $procurement): void
    {
        if (!$procurement->wasChanged(['status', 'current_phase_id'])) return;

        $previous = $procurement->getPrevious();
        $fromStatus = $procurement->wasChanged('status')
            ? ($previous['status'] ?? null)
            : $procurement->status;

        app(ProcurementEmailNotificationService::class)->procurementChanged(
            (int)$procurement->getKey(),
            $fromStatus ? strtoupper((string)$fromStatus) : null,
            strtoupper((string)$procurement->status),
            $procurement->wasChanged('current_phase_id'),
        );
    }
}
