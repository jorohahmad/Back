<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification implements ShouldQueue 
{
    use Queueable;

    protected $orderId;
    protected $netProfit;

    public function __construct($orderId, $netProfit)
    {
        $this->orderId = $orderId;
        $this->netProfit = $netProfit;
    }

    public function via(object $notifiable): array
    {
        return ['database']; 
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('messages.new_sale_title') ?? 'مبيع جديد!',
            'body'  => __('messages.new_sale_body', ['amount' => $this->netProfit]) ?? "تم بيع منتج لك بصافي ربح: {$this->netProfit}$",
            'transaction_id' => $this->orderId, // لحفظ رقم الطلب
            'type'  => 'sale',
        ];
    }
}