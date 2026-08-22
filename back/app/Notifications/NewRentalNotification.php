<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage; // 👈 استدعاء كلاس البث

class NewRentalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rentalId;
    protected $netProfit;

    public function __construct($rentalId, $netProfit)
    {
        $this->rentalId = $rentalId;
        $this->netProfit = $netProfit;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'new_rental_title', 
            'body_key'       => 'new_rental_body', 
            'amount'         => $this->netProfit,  
            'transaction_id' => $this->rentalId,  
            'type'           => 'rental',         
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => __('messages.new_rental_title'),
            'body'           => __('messages.new_rental_body', ['amount' => $this->netProfit]),
            'transaction_id' => $this->rentalId,
            'type'           => 'rental',
        ]);
    }
}