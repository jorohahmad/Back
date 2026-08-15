<?php

namespace App\Http\Controllers;

use App\Http\Resources\UsersResource;
use App\Mail\AdminMail;
use App\Mail\approvedUserMail;
use App\Mail\AcceptMail;
use App\Mail\RejectVerificationMail;
use App\Mail\rejectedUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class adminController extends Controller
{
    function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|string|confirmed',
        ]);
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = 'admin';
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    //login 1 to email and password 
    function login1(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        // اذا الايميل والباسورد موجودين في قاعدة البيانات
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        if ($user->role != 'admin') {
            return response()->json('You are not admin', 401);
        }
        if ($user->active != '1') {
            return response()->json('You are not admin', 401);
        }
        $user->update([
            'key' => random_int(1000, 9999)
        ]);
        Mail::to($user->email)->send(new AdminMail($user));
        return response()->json('Ok', 200);
    }

    //login 2 to confirmation admin email 
    function login2(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'key'   => 'required'
        ]);
        $user = User::where('email', $request->email)->firstOrFail();
        // التحقق من صحة الكود (OTP)
        if ($user->key != $request->key) {
            // 👈 نكتفي بإرجاع رسالة خطأ واضحة دون المساس بقاعدة البيانات
            return response()->json([
                'message' => 'الكود الذي أدخلته غير صحيح. يرجى التأكد والمحاولة مرة أخرى.'
            ], 401);
        }
        // ---------------------------------------------------------
        // إذا وصل الكود إلى هنا، فهذا يعني أن الـ OTP صحيح 100%
        // الآن نقوم بتصفير الكود (لحمايته من إعادة الاستخدام) وتوليد التوكن
        $user->update(['key' => null]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    // logout
    function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }

    //________________________________________________________________________

    // all users in application
    public function indexUsers()
    {
        $allUsers  = User::where('role', 'user')->get();
        return UsersResource::collection($allUsers);
    }

    // just pending users
    public function indexPendingAdmin()
    {
        $pendingUsers  = User::where('active', '0')->where('role','admin')->get();
        return response()->json($pendingUsers, 200);
    }

    //  just approved users
    public function indexApprovedUser()
    {
        $approvedUsers  = User::where('active', '1')->get();
        return response()->json($approvedUsers, 200);
    }
    public function acceptVerification()
    {
        $pendingUsers=User::where('active', '0')->where('role','user')->where('is_verified', true)->get();
        return response()->json($pendingUsers, 200);
    }
    //update active to approved mean '1'
    public function approveUser(Request $request)
    {
        $user = User::findOrFail($request->id);

        // active => 1
        $user->active = '1';
        $user->save();
        Mail::to($user->email)->queue(new AcceptMail($user));
        return response()->json('Approve User', 200);
    }
    public function approveAdmin(Request $request)
    {
        $user = User::findOrFail($request->id);

        // active => 1
        $user->active = '1';
        $user->save();
        Mail::to($user->email)->send(new approvedUserMail($user));
        return response()->json('Approve Admin', 200);
    }

    //update active to rejected mean '2'
    public function RejectUser(Request $request)
    {
        $user = User::findOrFail($request->id);

        //delete
        Mail::to($user->email)->queue(new RejectVerificationMail($user));

        $user->delete();
        return response()->json('Reject User', 200);
    }
    public function RejectAdmin(Request $request)
    {
        $user = User::findOrFail($request->id);

        //delete
        Mail::to($user->email)->send(new rejectedUserMail($user));

        $user->delete();
        return response()->json('Reject Admin', 200);
    }









    
    public function updateInformation(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only(['name', 'email']));

        return (new UsersResource($user))->additional([
            'message' => 'تم تحديث بياناتك الشخصية بنجاح.'
        ]);
    }

    public function updateImage(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $request->validate([
            'imagePersonal' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        // حتى لا يمتلئ السيرفر بآلاف الصور المهملة بمرور الوقت
        if ($user->imagePersonal) {
            Storage::disk('public')->delete($user->imagePersonal);
        }

        $newImagePath = saveFile($request->file('imagePersonal'), 'imagePersonal');
        $user->update([
            'imagePersonal' => $newImagePath
        ]);
        return response()->json([
            'message' => 'تم تحديث الصورة الشخصية بنجاح.',
            'image' => asset('storage/' . $user->imagePersonal),
        ], 200);
    }
}
