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

    public function indexForAdmin()
    {
        $admin = Auth::user();

        // 👈 التعديل هنا: استخدمنا get() بدلاً من paginate()
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

        // 👈 التعديل هنا: أزلنا مصفوفة الـ pagination من الاستجابة
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
}
