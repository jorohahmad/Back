<?php

namespace App\Http\Controllers;

use App\Mail\RegisterMail;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
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
        $user->password = Hash::make($request->password);

        if ($request->has('imagePersonal')) {
            $s = saveFile($request->file('imagePersonal'), 'imagePersonal');
            $user->imagePersonal = $s;
        }
        if ($request->has('imageId')) {
            $s = saveFile($request->file('imageId'), 'imageId');
            $user->imageId = $s;
        }

        $user->save();
        try {
            Mail::to($user->email)->send(new RegisterMail($user->name));
        } catch (Exception $ex) {
            return response()->json([
                'message' => ' failed to send email.',
                'error' => $ex->getMessage(),
            ], 200);
        }
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
}
