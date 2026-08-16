<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AccountApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'      => 'account_approved_title',
            'body_key'       => 'account_approved_body',
            'type'           => 'account_approved',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => __('messages.account_approved_title'),
            'body'  => __('messages.account_approved_body'),
            'type'  => 'account_approved',
        ]);
    }
}