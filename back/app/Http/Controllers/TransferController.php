<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Rental;
use Illuminate\Http\Request;
use App\Events\TransferStatusUpdated;
use App\Notifications\ItemReachedNotification;
use Illuminate\Support\Facades\Auth;
use App\Services\ReceiptService;
use App\Models\User;

class TransferController extends Controller
{
    protected $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    public function getAllTransfersForAdmin()
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->receiptService->getAllReceiptsForAdmin()
        ], 200);
    }

    public function markAsOnWay(Request $request, $transactionId)
    {
        $request->validate(['duration_minutes' => 'required|integer|min:1']);
        $duration = (int) $request->duration_minutes;
        $targetTime = now()->addMinutes($duration);
        $userId = null;

        $order = Order::where('transaction_id', $transactionId)->first();
        if ($order) {
            $order->update(['transfer_status' => 'onWay', 'expected_arrival_at' => $targetTime]);
            $userId = $order->buyer_id;
        }

        $rental = Rental::where('transaction_id', $transactionId)->first();
        if ($rental) {
            $rental->update(['transfer_status' => 'onWay', 'expected_arrival_at' => $targetTime]);
            $userId = $rental->renter_id;
        }

        if (!$userId) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $hours   = floor($request->duration_minutes / 60);
        $minutes = $request->duration_minutes % 60;
        $formattedDuration = sprintf('%02d:%02d:00', $hours, $minutes);

        broadcast(new TransferStatusUpdated($userId, $transactionId, 'onWay', $formattedDuration));

        return response()->json(['message' => 'Transaction shipped successfully.'],200);
    }

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
        
        $user =User::find($userId);
        if ($user) {
            $user->notify(new ItemReachedNotification($transactionId));
        }

        return response()->json(['message' => 'Transaction reached successfully.'],200);
    }

    public function markAsFinished($transactionId)
    {
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