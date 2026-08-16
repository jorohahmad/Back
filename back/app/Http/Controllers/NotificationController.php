<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * جلب جميع إشعارات المستخدم مع التصفح (Pagination)
     */
    public function index()
    {
        // 1. جلب الإشعارات من الداتا بيز مع التصفح
        $notifications = auth()->user()->notifications()->paginate(15);

        // 2. المرور على الإشعارات وترجمتها لحظياً
        $formattedNotifications = $notifications->getCollection()->map(function ($notification) {
            
            // جلب المفاتيح من الداتا بيز
            $titleKey = $notification->data['title_key'] ?? null;
            $bodyKey  = $notification->data['body_key'] ?? null;
            $amount   = $notification->data['amount'] ?? 0;

            // الترجمة: إذا وجدنا مفتاح نترجمه، وإذا كانت إشعارات قديمة نعرضها كما هي
            $title = $titleKey ? __("messages.{$titleKey}") : ($notification->data['title'] ?? 'إشعار جديد');
            $body  = $bodyKey ? __("messages.{$bodyKey}", ['amount' => $amount]) : ($notification->data['body'] ?? '');

            return [
                'id'             => $notification->id,
                'title'          => $title,
                'body'           => $body,
                'transaction_id' => $notification->data['transaction_id'] ?? null,
                'type'           => $notification->data['type'] ?? 'general',
                'is_read'        => $notification->read_at !== null,
                'created_at'     => $notification->created_at->diffForHumans(),
            ];
        });

        // 3. إرجاع النتيجة للفرونت إند مع بيانات الصفحات
        return response()->json([
            'data' => $formattedNotifications,
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
            ]
        ]);
    }
    /**
     * جلب عدد الإشعارات غير المقروءة (لعرض دائرة حمراء فوق أيقونة الجرس 🔔)
     */
    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();
        
        return response()->json([
            'status'       => 'success',
            'unread_count' => $count
        ], 200);
    }

    /**
     * تحديد إشعار محدد كمقروء، أو تحديد كل الإشعارات كمقروءة
     */
    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        
        if ($request->has('notification_id')) {
            // تحديد إشعار واحد كمقروء
            $notification = $user->notifications()->find($request->notification_id);
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            // تحديد كل الإشعارات كمقروءة دفعة واحدة
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.notifications_marked_read') ?? 'تم تحديث حالة الإشعارات بنجاح'
        ], 200);
    }
}