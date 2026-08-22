<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewProductAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $productId;
    protected $productTitle;

    public function __construct($productId, $productTitle)
    {
        $this->productId = $productId;
        $this->productTitle = $productTitle;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'new_announcement_title',
            'body_key'       => 'new_announcement_body',
            'product_name'   => $this->productTitle, 
            'product_id'     => $this->productId,
            'type'           => 'announcement',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => __('messages.new_announcement_title'),
            'body'           => __('messages.new_announcement_body', ['product_name' => $this->productTitle]),
            'product_id'     => $this->productId,
            'type'           => 'announcement',
        ]);
    }
}