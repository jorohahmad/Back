<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage; // 👈 استدعاء كلاس البث

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
        // 👈 دمجنا المستوى الأول (قاعدة البيانات) مع المستوى الثاني (البث اللحظي)
        return ['database', 'broadcast'];
    }

    // الدالة المسؤولة عن حفظ الإشعار في قاعدة البيانات
    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'new_sale_title', // اسم المفتاح
            'body_key'       => 'new_sale_body',  // اسم المفتاح
            'amount'         => $this->netProfit, // مبلغ الربح
            'transaction_id' => $this->orderId,
            'type'           => 'sale',
        ];
    }

    // هذه الدالة ترسل النص المترجم فوراً في الهواء (عبر Reverb)
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => __('messages.new_sale_title'),
            'body'           => __('messages.new_sale_body', ['amount' => $this->netProfit]),
            'transaction_id' => $this->orderId,
            'type'           => 'sale',
        ]);
    }
}