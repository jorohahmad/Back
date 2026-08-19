<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $transactionId;
    public $status;
    public $expectedArrivalAt;

    public function __construct($userId, $transactionId, $status, $expectedArrivalAt = null)
    {
        $this->userId = $userId;
        $this->transactionId = $transactionId;
        $this->status = $status;
        $this->expectedArrivalAt = $expectedArrivalAt;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'transfer.updated'; // الاسم الذي سيستمع له تطبيق Flutter
    }
}