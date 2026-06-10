<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Mail\RegisterMail;
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

        if ($request->has('imagePersonal')) {
            $s = saveFile($request->file('imagePersonal'), 'imagePersonal');
            $user->imagePersonal = $s;
        }
        if ($request->has('imageId')) {
            $s = saveFile($request->file('imageId'), 'imageId');
            $user->imageId = $s;
        }


        try {
            Mail::to($user->email)->send(new RegisterMail($user->name));
        } catch (Exception $ex) {
            return response()->json([
                'message' => ' failed to send email.',
                'error' => $ex->getMessage(),
            ], 200);
        }
        $user->save();
        return response()->json([
            'message' => 'User registered successfully',
            'image' => asset('storage/' . $user->imagePersonal),
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
        //اذا  الادمن وافق او لا
        if ($user->active == "0") {
            return response()->json([
                'message' => 'Your account is not active yet. Please wait for admin approval.',
            ], 403);
        }
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
}
