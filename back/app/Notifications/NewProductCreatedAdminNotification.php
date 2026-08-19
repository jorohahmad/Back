<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewProductCreatedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $productTitle;
    protected $ownerName;

    public function __construct($productTitle, $ownerName)
    {
        $this->productTitle = $productTitle;
        $this->ownerName = $ownerName;
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'    => 'admin_new_product_title',
            'body_key'     => 'admin_new_product_body',
            'product_name' => $this->productTitle,
            'owner_name'   => $this->ownerName,
            'type'         => 'admin_new_product',
        ];
    }
}