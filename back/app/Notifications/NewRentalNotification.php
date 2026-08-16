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
        // 👈 المستوى الأول + المستوى الثاني
        return ['database', 'broadcast'];
    }

    // حفظ مفاتيح الإيجار في قاعدة البيانات
    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'new_rental_title', // مفتاح الإيجار
            'body_key'       => 'new_rental_body',  // مفتاح الإيجار
            'amount'         => $this->netProfit,   // مبلغ الإيجار الصافي
            'transaction_id' => $this->rentalId,     // رقم عملية الإيجار
            'type'           => 'rental',           // نوع الإشعار: إيجار
        ];
    }

    // إرسال النص المترجم للإيجار عبر البث اللحظي (Reverb)
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