<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

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
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('messages.new_rental_title') ?? 'تأجير جديد!',
            'body'  => __('messages.new_rental_body', ['amount' => $this->netProfit]) ?? "تم تأجير آلتك بصافي ربح: {$this->netProfit}$",
            'transaction_id' => $this->rentalId,
            'type'  => 'rental',
        ];
    }
}