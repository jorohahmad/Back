<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ItemReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $transactionId;

    public function __construct($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'item_reached_title',
            'body_key'       => 'item_reached_body',
            'transaction_id' => $this->transactionId,
            'type'           => 'item_reached',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => __('messages.item_reached_title') ?? 'آلتك بانتظارك!',
            'body'           => __('messages.item_reached_body') ?? 'لقد وصلت الآلات إلى المكتب بنجاح. يرجى التفضل باستلامها.',
            'transaction_id' => $this->transactionId,
            'type'           => 'item_reached',
        ]);
    }
}