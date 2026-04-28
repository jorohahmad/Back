<?php

namespace App\Http\Controllers;

use App\Mail\AdminMail;
use App\Mail\approvedUserMail;
use App\Mail\rejectedUserMail;
use App\Models\User;
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
        $user->key = random_int(1000, 9999);
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
        if ($user->key != $request->key) {
            $user->update([
                'key' => (string)random_int(1000, 9999)
            ]);
            return response()->json([
                'message' => 'Invalid key',
            ], 401);
        }
        $user->update([
            'key' => (string)random_int(1000, 9999)
        ]);

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
        return response()->json($allUsers, 200);
    }

    // just pending users
    public function indexPendingUser()
    {
        $pendingUsers  = User::where('active', '0')->get();
        return response()->json($pendingUsers, 200);
    }

    //  just approved users
    public function indexApprovedUser()
    {
        $approvedUsers  = User::where('active', '1')->get();
        return response()->json($approvedUsers, 200);
    }
    // just rejected users
    public function indexRejectedUser()
    {
        $rejectedUsers  = User::onlyTrashed()->get();

        return response()->json($rejectedUsers, 200);
    }

    //update active to approved mean '1'
    public function approveUser(Request $request)
    {
        $user = User::findOrFail($request->id);

        // active => 1
        $user->active = '1';
        $user->save();
        Mail::to($user->email)->send(new approvedUserMail($user));
        return response()->json('Approve User', 200);
    }

    //update active to rejected mean '2'
    public function RejectUser(Request $request)
    {
        $user = User::findOrFail($request->id);

        // Soft delete
        $user->delete();
        Mail::to($user->email)->send(new rejectedUserMail($user));
        return response()->json('Reject User', 200);
    }
}
