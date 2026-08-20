<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewAdminRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $newAdminName;

    public function __construct($newAdminName)
    {
        $this->newAdminName = $newAdminName;
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'  => 'admin_action_new_admin_title',
            'body_key'   => 'admin_action_new_admin_body',
            'admin_name' => $this->newAdminName,
            'type'       => 'admin_action_log', 
        ];
    }
}