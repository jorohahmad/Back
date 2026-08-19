<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewCheckoutAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $transactionId;
    protected $buyerName;

    public function __construct($transactionId, $buyerName)
    {
        $this->transactionId = $transactionId;
        $this->buyerName = $buyerName;
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'admin_new_checkout_title',
            'body_key'       => 'admin_new_checkout_body',
            'transaction_id' => $this->transactionId,
            'buyer_name'     => $this->buyerName,
            'type'           => 'admin_new_checkout',
        ];
    }
}