<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Http\Resources\UsersResource;
use App\Mail\AcceptMail;
use App\Mail\RegisterMail;
use App\Mail\ForgetPasswordMail;
use App\Mail\VerificationMail;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|string',
        ]);
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        try {
            Mail::to($user->email)->queue(new RegisterMail($user->name));
        } catch (Exception $ex) {
            return response()->json([
                'message' => ' failed to send email.',
                'error' => $ex->getMessage(),
            ], 200);
        }
        $user->save();
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }
    /////////////////////////////////////// login and logout
    function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        // اذا الايميل والباسورد موجودين في قاعدة البيانات
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        //  Mail::to($user->email)->queue(new AcceptMail($user));
        //اذا  الادمن وافق او لا
        // if ($user->active == "0") {
        //     return response()->json([
        //         'message' => 'Your account is not active yet. Please wait for admin approval.',
        //     ], 403);
        // }
        $user['imagePersonal'] = asset('storage/' . $user->imagePersonal);
        $user['imageId'] = asset('storage/' . $user->imageId);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }
    function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }
    ///////////////////////favorites///////////////////////
    public function toggleFavorite(Request $request)
    {
        $request->validate([
            'productId' => 'required|exists:products,id'
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $result = $user->favorites()->toggle($request->productId);
        $isFavorited = count($result['attached']) > 0;
        return response()->json([
            'message' => $isFavorited ? 'تمت الإضافة إلى المفضلة' : 'تم الإزالة من المفضلة',
            'is_favorited' => $isFavorited
        ], 200);
    }

    public function getFavorites()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $favorites = $user->favorites()->get();
        return FavoriteResource::collection($favorites);
    }

    ///////////////////////register and login from google///////////////////////

    public function googleRegisterOrLogin(Request $request)
    {
        $request->validate([
            'provider_token' => 'required|string',
        ]);
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->provider_token);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                ]
            );
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            // الحالة الأولى: تم إنشاء الحساب للتو (تسجيل جديد)
            if ($user->wasRecentlyCreated) {
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'تم إنشاء حسابك بنجاح. يرجى الانتظار حتى تتم الموافقة عليه من قبل الإدارة.'
                ], 403); // 403 تعني Forbidden (ممنوع الدخول حالياً)
            }

            // الحالة الثانية: الحساب قديم، لكن الإدمن لم يوافق عليه بعد (أو قام بحظره)
            if (!$user->active) {
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'حسابك لا يزال قيد المراجعة أو تم إيقافه من قبل الإدارة.'
                ], 403);
            }

            // الحالة الثالثة: الحساب قديم وتمت الموافقة عليه من الإدمن (تسجيل دخول ناجح)
            $authToken = $user->createToken('MobileAppAuthToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح.',
                'token' => $authToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Failed to authenticate with Google.',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }
    public function Verification1()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'key' => (string)random_int(1000, 9999)
        ]);
        Mail::to($user->email)->queue(new VerificationMail($user));
        return response()->json([
            'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني. يرجى التحقق من صندوق الوارد أو البريد العشوائي.',
        ], 200);
    }
    public function Verification2(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();
         // التحقق من صحة الكود
        if ($user->key !== $request->key) {
            return response()->json([
                'message' => 'الكود المدخل غير صحيح. يرجى التأكد والمحاولة.'
            ], 400);
        }

        // إذا كان الكود صحيحاً، نعطي الضوء الأخضر للفرونت إند لإظهار حقل الباسوورد
        return response()->json([
            'message' => 'الكود صحيح.',
            'is_valid' => true // هذه القيمة سيستخدمها الفرونت إند برمجياً
        ], 200);

    }
    public function AccountVerification(Request $request)
    {
        // 1. التحقق الصارم من الملفات
        $request->validate([
            'imagePersonal' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'imageId'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 2. التحقق مما إذا كان الحساب موثقاً مسبقاً لمنع التكرار
        if ($user->is_verified) {
            return response()->json([
                'message' => 'حسابك موثق مسبقاً.'
            ], 400);
        }

        // 3. مسح الصور القديمة من السيرفر (إن وجدت) لتوفير المساحة
        if ($user->imagePersonal) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->imagePersonal);
        }
        if ($user->imageId) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->imageId);
        }

        $personalPath = saveFile($request->file('imagePersonal'), 'imagePersonal');
        $idPath       = saveFile($request->file('imageId'), 'imageId');

        $user->update([
            'imagePersonal' => $personalPath,
            'imageId'       => $idPath,
            'is_verified'   => true,
            'key'=>null,
        ]);

        return (new UsersResource($user))->additional([
            'message' => 'تم توثيق حسابك بنجاح. شكراً لتعاونك.'
        ]);
    }

    function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'هذا البريد الإلكتروني غير مسجل لدينا.'
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update([
            'key' => (string)random_int(1000, 9999)
        ]);
        Mail::to($user->email)->queue(new ForgetPasswordMail($user));
        return response()->json([
            'message' => 'Password reset code sent to your email',
        ], 200);
    }
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'key' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // التحقق من صحة الكود
        if ($user->key !== $request->key) {
            return response()->json([
                'message' => 'الكود المدخل غير صحيح. يرجى التأكد والمحاولة.'
            ], 400);
        }

        // إذا كان الكود صحيحاً، نعطي الضوء الأخضر للفرونت إند لإظهار حقل الباسوورد
        return response()->json([
            'message' => 'الكود صحيح.',
            'is_valid' => true // هذه القيمة سيستخدمها الفرونت إند برمجياً
        ], 200);
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'key' => null // 👈 مسح الكود فوراً لكي لا يُستخدم مرة أخرى
        ]);
        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.'
        ], 200);
    }

    public function changePassword(Request $request)
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

    // 2. جلب المستخدم الحالي (الذي يرسل التوكن الخاص به)
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 3. التأكد من أن كلمة المرور القديمة التي أدخلها صحيحة
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور القديمة غير صحيحة.'
            ], 400);
        }

        // 4. تشفير وحفظ كلمة المرور الجديدة
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'تم تحديث كلمة المرور بنجاح.'
        ], 200);
    }
}
