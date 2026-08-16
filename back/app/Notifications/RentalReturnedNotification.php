<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class RentalReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rentalId;

    public function __construct($rentalId)
    {
        $this->rentalId = $rentalId;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'rental_returned_title',
            'body_key'       => 'rental_returned_body',
            'transaction_id' => $this->rentalId,
            'type'           => 'rental_returned',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => __('messages.rental_returned_title'),
            'body'           => __('messages.rental_returned_body'),
            'transaction_id' => $this->rentalId,
            'type'           => 'rental_returned',
        ]);
    }
}