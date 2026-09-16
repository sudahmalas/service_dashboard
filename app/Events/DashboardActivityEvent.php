<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DashboardActivityEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $id;
    public string $type;
    public string $clientName;
    public string $status;
    public string $message;
    public string $timestamp;

    /**
     * Create a new activity event for dashboard monitoring.
     */
    public function __construct(string $type, string $clientName, string $status, string $message)
    {
        $this->id = (string) Str::uuid();
        $this->type = $type;
        $this->clientName = $clientName;
        $this->status = $status;
        $this->message = $message;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard.activity'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'activity.logged';
    }

    /**
     * Broadcast payload.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'client_name' => $this->clientName,
            'status' => $this->status,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
        ];
    }
}
