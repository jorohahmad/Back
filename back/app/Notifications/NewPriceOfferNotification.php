<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewPriceOfferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offerId;
    protected $productTitle;
    protected $proposedPrice;

    public function __construct($offerId, $productTitle, $proposedPrice)
    {
        $this->offerId = $offerId;
        $this->productTitle = $productTitle;
        $this->proposedPrice = $proposedPrice;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'new_offer_title',
            'body_key'       => 'new_offer_body',
            'product_name'   => $this->productTitle,
            'amount'         => $this->proposedPrice,
            'transaction_id' => $this->offerId,
            'type'           => 'price_offer',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => __('messages.new_offer_title'),
            'body'           => __('messages.new_offer_body', ['product_name' => $this->productTitle, 'amount' => $this->proposedPrice]),
            'transaction_id' => $this->offerId,
            'type'           => 'price_offer',
        ]);
    }
}