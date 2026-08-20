<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewImportantNoticeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $noticeTitle;
    protected $noticeType;
    protected $adminName;

    public function __construct($noticeTitle, $noticeType, $adminName)
    {
        $this->noticeTitle = $noticeTitle;
        $this->noticeType = $noticeType;
        $this->adminName = $adminName;
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title_key'    => 'admin_action_new_notice_title',
            'body_key'     => 'admin_action_new_notice_body',
            'notice_title' => $this->noticeTitle,
            'notice_type'  => $this->noticeType,
            'admin_name'   => $this->adminName,
            'type'         => 'admin_action_log', 
        ];
    }
}