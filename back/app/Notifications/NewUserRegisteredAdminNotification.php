<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewUserRegisteredAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $userName;

    public function __construct($userName)
    {
        $this->userName = $userName;
    }

    public function via(object $notifiable): array
    {
        return ['database']; 
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key' => 'admin_new_user_title',
            'body_key'  => 'admin_new_user_body',
            'user_name' => $this->userName,
            'type'      => 'admin_new_user',
        ];
    }
}