<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PriceOffer;

class NotificationController extends Controller
{
    /**
     * جلب جميع إشعارات المستخدم مع التصفح (Pagination)
     */
    public function index(Request $request)
    {
        // 1. جلب الإشعارات بالطريقة العادية
        $notifications = $request->user()->notifications()->paginate(10);

        // 2. التعديل على البيانات قبل إرسالها للفرونت إند
        $notifications->getCollection()->transform(function ($notification) {
            $data = $notification->data;

            // 3. إذا كان الإشعار من نوع "عرض سعر"، نبحث عن حالة العرض الحالية
            if (isset($data['type']) && $data['type'] === 'price_offer') {
                $offer = PriceOffer::find($data['transaction_id']);

                // إضافة حقل جديد للفرونت إند يخبره بالحالة الحالية للعرض
                $data['offer_status'] = $offer ? $offer->status : 'deleted';

                // تحديث الـ data داخل الكولكشن (للعرض فقط، لا نعدل الداتا بيز)
                $notification->data = $data;
            }

            return [
                'id' => $notification->id,
                'title' => __('messages.' . ($data['title_key'] ?? '')),
                // تمرير المتغيرات المطلوبة للرسالة إذا كانت موجودة
                'body' => __('messages.' . ($data['body_key'] ?? ''), $data),
                'transaction_id' => $data['transaction_id'] ?? null,
                'type' => $data['type'] ?? null,
                'is_read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->diffForHumans(),

                // 👈 الحقل السحري الجديد الذي سيختبره الفرونت إند
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

    public function delete($notificationId)
    {
        $notification = Auth::user()->notifications()->where('id', $notificationId)->first();
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->delete();
        return response()->json(['message' => 'Notification deleted successfully'],204);
    }
}
