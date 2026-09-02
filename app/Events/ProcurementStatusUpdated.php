<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcurementStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $procurementId,
        public readonly int $officeId,
        public readonly ?int $userId,
        public readonly string $action,
        public readonly string $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast strictly to target user if specified, otherwise broadcast to the whole office channel
        return [
            $this->userId
                ? new PrivateChannel('user.' . $this->userId)
                : new PrivateChannel('office.' . $this->officeId),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->userId !== null ? 'procurement.user_updated' : 'procurement.office_updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'procurement_id' => $this->procurementId,
            'office_id'      => $this->officeId,
            'user_id'        => $this->userId,
            'action'         => $this->action,
            'message'        => $this->message,
            'url'            => route('procurements.show', $this->procurementId),
        ];
    }
}
