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

class NoticeController extends Controller
{
    use AuthorizesRequests;
    // عرض كل الملاحظات التي تنطبق عليها الشروط المطلوبة
    public function index(Request $request)
{
    // 1. جلب المستخدم الحالي بشكل آمن عبر Sanctum
    $user = $request->user();

    // حماية إضافية في حال مر الطلب بدون مستخدم
    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    // 2. فحص الصلاحيات (مع التأكد أن الدالة موجودة فعلاً في المودل لتجنب الخطأ 500)
    if (!method_exists($user, 'isAdmin') || !$user->isAdmin()) {
        return response()->json([
            'message' => 'You are not admin'
        ], 403);
    }

    // 3. جلب البيانات
    $notices = Notice::where(function ($query) use ($user) {
        $query->where('type', '!=', 'personal')
              ->orWhere(function ($q) use ($user) {
                  $q->where('type', 'personal')
                    ->where('user_id', $user->id);
              });
    })
    ->orderBy('due_date', 'asc')
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

        $personalNotices = $user->notices()->where('user_id','=',$user->id)->where('type','=','personal')->get();
        return response()->json([
            'status' => 'success',
            'data' => $personalNotices
        ], 201);
    }
}
