<?php

namespace Modules\Finance\App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorklistUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $module,
        public readonly string $action,
        public readonly int    $requestId,
        public readonly int    $patientId,
        public readonly bool   $isUrgent = false,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("worklist.{$this->module}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'worklist.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'module'     => $this->module,
            'action'     => $this->action,
            'request_id' => $this->requestId,
            'patient_id' => $this->patientId,
            'is_urgent'  => $this->isUrgent,
            'timestamp'  => now()->toISOString(),
        ];
    }
}