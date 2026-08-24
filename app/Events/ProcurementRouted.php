<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcurementRouted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Procurement $procurement;
    public int $destinationOfficeId;
    public string $message;

    public function __construct(Procurement $procurement, int $destinationOfficeId, string $message)
    {
        $this->procurement = $procurement;
        $this->destinationOfficeId = $destinationOfficeId;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('office.' . $this->destinationOfficeId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'procurement.routed';
    }
}
