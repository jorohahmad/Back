<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PriceOfferAcceptedNotification extends Notification implements ShouldQueue
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
            'title_key'    => 'offer_accepted_title',
            'body_key'     => 'offer_accepted_body',
            'product_name' => $this->productTitle,
            'type'         => 'offer_accepted',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => __('messages.offer_accepted_title'),
            'body'  => __('messages.offer_accepted_body', ['product_name' => $this->productTitle]),
            'type'  => 'offer_accepted',
        ]);
    }
}