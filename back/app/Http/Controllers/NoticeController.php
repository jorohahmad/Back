<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoticeRequest;
use App\Http\Requests\UpdateNoticeRequest;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewImportantNoticeNotification;

class NoticeController extends Controller
{
    use AuthorizesRequests;
    // عرض كل الملاحظات التي تنطبق عليها الشروط المطلوبة
    public function index()
    {

        $user = Auth::user();

        if (!$user->role=='admin') {
            return response()->json([
                'message' => 'are not admin'
            ], 403);
        }
        // if ($user->role !== 'admin') {
        //     return response()->json(['message' => 'you are not admin'], 403);
        // }

        $notices = Notice::where(function ($quary) use ($user) {
            $quary->where('type', '!=', 'personal')
                ->orWhere(function ($q) use ($user) {
                    $q->where('type', 'personal')
                        ->where('user_id', $user->id);
                });
        })
            ->OrderBy('due_date', 'asc')
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $notices
        ], 200);
    }

    public function store(StoreNoticeRequest $request): JsonResponse
    {
        $userId = Auth::user()->id;
        $user = User::findOrFail($userId);

        $validated = $request->validated();
        $notice = $user->notices()->create($validated);
        if (in_array($notice->type, ['urgent', 'project'])) {
            $otherAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->get();
            Notification::send($otherAdmins, new NewImportantNoticeNotification($notice->title, $notice->type, $user->name));
        }
        return response()->json([
            'status' => ' success',
            'message' => 'added sucessful',
            'data' => $notice
        ], 201);
    }
    public function update(UpdateNoticeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $notice = Notice::findOrFail($request->id);
        $notice->update($validated);

        return response()->json([
            'status' => ' success',
            'message' => 'updated sucessful',
            'data' => $notice
        ], 201);
    }


    public function destroy(Request $request): JsonResponse
    {
        $notice = Notice::findOrFail($request->id);
        $notice->delete();
        return response()->json([
            'message' => 'deleted successful'
        ], 204);
    }

    public function personalNotice(Request $request): JsonResponse
    {
        $user = Auth::user();

        $personalNotices = $user->notices()->where('user_id', '=', $user->id)->where('type', '=', 'personal')->get();
        return response()->json([
            'status' => 'success',
            'data' => $personalNotices
        ], 201);
    }
}
