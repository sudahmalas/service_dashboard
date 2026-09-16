<?php

namespace App\Events;

use App\Models\Client;
use App\Models\MessageQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DispatchedClientPayloadEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public MessageQueue $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, MessageQueue $message)
    {
        $this->client = $client;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to both legacy 'printer.{slug}' and secured 'private-client.{id}'.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('printer.' . $this->client->slug),
            new PrivateChannel('client.' . $this->client->id),
        ];
    }

    /**
     * The event's broadcast name.
     * Compatible with printer_service listening to 'PrintJobDispatched'
     * and v1 clients listening to 'client.payload.received'.
     */
    public function broadcastAs(): string
    {
        return 'PrintJobDispatched';
    }

    /**
     * Get the data that should be broadcasted.
     */
    public function broadcastWith(): array
    {
        $payload = $this->message->payload;
        $jobs = [];

        if (is_array($payload)) {
            if (isset($payload['jobs']) && is_array($payload['jobs'])) {
                $jobs = $payload['jobs'];
            } else {
                $jobs = [$payload];
            }
        }

        return [
            'id' => $this->message->id,
            'type' => $this->message->type,
            'machineName' => $this->client->slug,
            'service_name' => $this->client->name,
            'payload' => $payload,
            'jobs' => $jobs,
            'created_at' => $this->message->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
