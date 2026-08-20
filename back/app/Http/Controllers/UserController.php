<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Http\Resources\UsersResource;
use App\Http\Resources\SellerProfileResource;
use App\Mail\AcceptMail;
use App\Mail\RegisterMail;
use App\Mail\ForgetPasswordMail;
use App\Mail\VerificationMail;
use App\Models\Product;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewUserRegisteredAdminNotification;
use App\Notifications\VerificationRequestAdminNotification;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

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
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new NewUserRegisteredAdminNotification($user->name));
        } catch (Exception $ex) {
            return response()->json([
                'message' => __('messages.email_send_failed'),
                'error' => $ex->getMessage(),
            ], 200);
        }

        $user->save();
        return response()->json([
            'message' => __('messages.user_registered_successfully'),
            'user' => $user,
        ], 201);
    }

    function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => __('messages.invalid_credentials'),
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $user['imagePersonal'] = asset('storage/' . $user->imagePersonal);
        $user['imageId'] = asset('storage/' . $user->imageId);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => __('messages.login_successful'),
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => __('messages.logout_successful'),
        ], 200);
    }

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
            // 👈 لاحظ كيف نستخدم الترجمة مع العمليات الشرطية
            'message' => $isFavorited ? __('messages.added_to_favorites') : __('messages.removed_from_favorites'),
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

            if ($user->wasRecentlyCreated) {
                $admins = User::where('role', 'admin')->get();
                Notification::send($admins, new NewUserRegisteredAdminNotification($user->name));
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => __('messages.account_created_pending_approval')
                ], 403);
            }

            $authToken = $user->createToken('MobileAppAuthToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => __('messages.login_successful'),
                'token' => $authToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'message' => __('messages.invalid_credentials'),
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
            'message' => __('messages.verification_code_sent'),
        ], 200);
    }

    public function Verification2(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->key !== $request->key) {
            return response()->json([
                'message' => __('messages.invalid_verification_code')
            ], 400);
        }

        return response()->json([
            'message' => __('messages.code_is_valid'),
            'is_valid' => true
        ], 200);
    }

    public function AccountVerification(Request $request)
    {
        $request->validate([
            'imagePersonal' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'imageId'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->is_verified) {
            return response()->json([
                'message' => __('messages.account_already_verified')
            ], 400);
        }

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
            'key' => null,
        ]);

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new VerificationRequestAdminNotification(auth()->user()->name));

        return (new UsersResource($user))->additional([
            'message' => __('messages.account_verified_successfully')
        ]);
    }

    function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            // 👈 تخصيص رسالة الـ validation المترجمة
            'email.exists' => __('messages.email_not_registered')
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update([
            'key' => (string)random_int(1000, 9999)
        ]);
        Mail::to($user->email)->queue(new ForgetPasswordMail($user));
        return response()->json([
            'message' => __('messages.password_reset_code_sent'),
        ], 200);
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'key' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->key !== $request->key) {
            return response()->json([
                'message' => __('messages.invalid_verification_code')
            ], 400);
        }

        return response()->json([
            'message' => __('messages.code_is_valid'),
            'is_valid' => true
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
            'key' => null
        ]);
        return response()->json([
            'message' => __('messages.password_changed_successfully')
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => __('messages.old_password_incorrect')
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => __('messages.password_updated_successfully')
        ], 200);
    }

    public function getSellerProfile(User $seller)
    {
        $sellerData = $this->userService->getSellerWithActiveProducts($seller);

        return response()->json([
            'status' => 'success',
            'data'   => new SellerProfileResource($sellerData)
        ], 200);
    }

    public function getMyStats(Request $request)
    {
        $seller = $request->user();
        $stats = $this->userService->getSellerStats($seller);

        return response()->json([
            'status' => 'success',
            'data'   => $stats
        ], 200);
    }

    //Rating
    public function rateSeller(Request $request, User $seller)
    {
        $request->validate([
            'score'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $rating = $this->userService->rateUser(
                $request->user(),
                $seller,
                $request->score,
                $request->comment
            );

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.rating_successful'),
                'data'    => $rating
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function recharge(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);
        $userId=Auth::id();

        $newBalance=DB::transaction(function () use ($userId, $request) {
            $user =User::lockForUpdate()->find($userId);
            $user->increment('balance', $request->amount);
            return $user->balance; 
        });

        return response()->json([
            'status'      => 'success',
            'message'     => 'تم شحن الرصيد بنجاح',
            'new_balance' => round($newBalance, 2),
        ], 200);
    }
}
