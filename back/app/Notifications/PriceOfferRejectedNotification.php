<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PriceOfferRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $productTitle;

    public function __construct($productTitle)
    {
        $this->productTitle = $productTitle;
    }

    public function via(object $notifiable): array { return ['database', 'broadcast']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'    => 'offer_rejected_title',
            'body_key'     => 'offer_rejected_body',
            'product_name' => $this->productTitle,
            'type'         => 'offer_rejected',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => __('messages.offer_rejected_title'),
            'body'  => __('messages.offer_rejected_body', ['product_name' => $this->productTitle]),
            'type'  => 'offer_rejected',
        ]);
    }
}