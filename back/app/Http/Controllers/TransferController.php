<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Rental;
use Illuminate\Http\Request;
use App\Events\TransferStatusUpdated;
use App\Notifications\ItemReachedNotification;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    // 1. شحن السلة كاملة
    public function markAsOnWay(Request $request, $transactionId)
    {
        $request->validate(['duration_minutes' => 'required|integer|min:1']);
        $duration = (int) $request->duration_minutes;
        $targetTime = now()->addMinutes($duration);
        $userId = null;

        // تحديث المبيعات المرتبطة بهذه السلة (إن وجدت)
        $order = Order::where('transaction_id', $transactionId)->first();
        if ($order) {
            $order->update(['transfer_status' => 'onWay', 'expected_arrival_at' => $targetTime]);
            $userId = $order->buyer_id;
        }

        // تحديث الإيجارات المرتبطة بهذه السلة (إن وجدت)
        $rental = Rental::where('transaction_id', $transactionId)->first();
        if ($rental) {
            $rental->update(['transfer_status' => 'onWay', 'expected_arrival_at' => $targetTime]);
            $userId = $rental->renter_id;
        }

        // التأكد من وجود سلة بهذا الرقم
        if (!$userId) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // تنسيق الوقت للـ Flutter
        $hours   = floor($request->duration_minutes / 60);
        $minutes = $request->duration_minutes % 60;
        $formattedDuration = sprintf('%02d:%02d:00', $hours, $minutes);

        // إطلاق الحدث (بدون نوع، لأن السلة كاملة انشحنت)
        broadcast(new TransferStatusUpdated($userId, $transactionId, 'onWay', $formattedDuration));

        return response()->json(['message' => 'Transaction shipped successfully.']);
    }

    // 2. وصول السلة للمكتب
    public function markAsReached($transactionId)
    {
        $userId = null;

        $order = Order::where('transaction_id', $transactionId)->first();
        if ($order) {
            $order->update(['transfer_status' => 'reached']);
            $userId = $order->buyer_id;
        }

        $rental = Rental::where('transaction_id', $transactionId)->first();
        if ($rental) {
            $rental->update(['transfer_status' => 'reached']);
            $userId = $rental->renter_id;
        }

        if (!$userId) return response()->json(['message' => 'Not found'], 404);

        broadcast(new TransferStatusUpdated($userId, $transactionId, 'reached', '00:00:00'));
        
        // إرسال الإشعار
        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->notify(new ItemReachedNotification($transactionId));
        }

        return response()->json(['message' => 'Transaction reached successfully.']);
    }

    // 3. تأكيد استلام السلة كاملة
    public function markAsFinished($transactionId)
    {
        // حماية أمنية: التأكد من أن المستخدم الحالي هو صاحب هذه السلة
        $order = Order::where('transaction_id', $transactionId)->first();
        $rental = Rental::where('transaction_id', $transactionId)->first();
        
        $ownerId = $order ? $order->buyer_id : ($rental ? $rental->renter_id : null);

        if (Auth::id() !== $ownerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order) $order->update(['transfer_status' => 'finished']);
        if ($rental) $rental->update(['transfer_status' => 'finished']);

        return response()->json(['message' => 'Transaction received successfully.']);
    }
}