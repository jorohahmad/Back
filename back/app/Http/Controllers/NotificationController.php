<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PriceOffer;
use App\Notifications\NewAdminRegisteredNotification;
use App\Notifications\NewImportantNoticeNotification;
class NotificationController extends Controller
{

    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(10);

        $notifications->getCollection()->transform(function ($notification) {
            $data = $notification->data;

            if (isset($data['type']) && $data['type'] === 'price_offer') {
                $offer = PriceOffer::find($data['transaction_id']);

                $data['offer_status'] = $offer ? $offer->status : 'deleted';

                $notification->data = $data;
            }

            return [
                'id' => $notification->id,
                'title' => __('messages.' . ($data['title_key'] ?? '')),
                'body' => __('messages.' . ($data['body_key'] ?? ''), $data),
                'transaction_id' => $data['transaction_id'] ?? null,
                'type' => $data['type'] ?? null,
                'is_read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->diffForHumans(),

                'offer_status' => $data['offer_status'] ?? null,
            ];
        });

        return response()->json([
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ]
        ]);
    }

    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();

        return response()->json([
            'status'       => 'success',
            'unread_count' => $count
        ], 200);
    }


    public function markAsRead(Request $request)
    {
        $user = Auth::user();

        if ($request->has('notification_id')) {
            $notification = $user->notifications()->find($request->notification_id);
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.notifications_marked_read') ?? 'تم تحديث حالة الإشعارات بنجاح'
        ], 200);
    }

    public function delete($notificationId)
    {
        $notification = Auth::user()->notifications()->where('id', $notificationId)->first();
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->delete();
        return response()->json(['message' => 'Notification deleted successfully'],200);
    }

    public function indexForAdmin()
    {
        $admin = Auth::user();

        $notifications = $admin->notifications()->get();

        $formattedNotifications = $notifications->map(function ($notification) {
            $data = $notification->data;

            $replace = [];
            if (isset($data['user_name'])) $replace['user_name'] = $data['user_name'];
            if (isset($data['product_name'])) $replace['product_name'] = $data['product_name'];
            if (isset($data['owner_name'])) $replace['owner_name'] = $data['owner_name'];
            if (isset($data['buyer_name'])) $replace['buyer_name'] = $data['buyer_name'];
            if (isset($data['transaction_id'])) $replace['transaction_id'] = $data['transaction_id'];

            return [
                'id'         => $notification->id,
                'type'       => $data['type'] ?? 'admin_notification',
                'title'      => __('messages.' . ($data['title_key'] ?? ''), $replace),
                'body'       => __('messages.' . ($data['body_key'] ?? ''), $replace),
                'is_read'    => $notification->read_at !== null,
                'created_at' => $notification->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formattedNotifications
        ]);
    }
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديد جميع الإشعارات كمقروءة.'
        ]);
    }
    public function adminActivityLog()
    {
        $admin = Auth::user();

        $activities = $admin->notifications()
            ->whereIn('type', [
                NewAdminRegisteredNotification::class,
                NewImportantNoticeNotification::class,
            ])
            ->latest()
            ->get();

        $formattedActivities = $activities->map(function ($notification) {
            $data = $notification->data;
            
            $replace = [];
            if (isset($data['admin_name'])) $replace['admin_name'] = $data['admin_name'];
            if (isset($data['notice_title'])) $replace['notice_title'] = $data['notice_title'];
            if (isset($data['notice_type'])) $replace['notice_type'] = $data['notice_type'];

            return [
                'id'         => $notification->id,
                'type'       => $data['type'] ?? 'admin_action_log',
                'title'      => __('messages.' . ($data['title_key'] ?? ''), $replace),
                'body'       => __('messages.' . ($data['body_key'] ?? ''), $replace),
                'is_read'    => $notification->read_at !== null,
                'created_at' => $notification->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formattedActivities
        ], 200);
    }
}
