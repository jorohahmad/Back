<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SayHelloEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    // هنا نحدد اسم "القناة" التي سنبث عليها (قناة عامة للتجربة)
    public function broadcastOn(): array
    {
        return [
            new Channel('joroh'),
        ];
    }

    // اسم الحدث الذي سيستمع إليه الفرونت إند
    public function broadcastAs(): string
    {
        return 'hello';
    }
}
