<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    function register(Request $request){
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|string|confirmed',
        ]);
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        //   $personalImage=base64_decode($request->personalImage);
        // $personalImageName='M/'.time().'.jpg';
        // $path1=storage_path('app/public/'.$personalImageName);
        // file_put_contents($path1,$personalImage);
        // $validated['personalImage'] = str_replace('M/', '', $personalImageName);
        if($request->has('imagePersonal')){
            $image=$request->imagePersonal;
            $extension=$image->getClientOriginalExtension();
            $fileName=time().rand(1,1000).'.'.$extension;
            $image->move(public_path("imagePersonal"),$fileName);
            $user->imagePersonal=$fileName;
        }
        if($request->has('imageId')){
            $image=$request->imageId;
            $extension=$image->getClientOriginalExtension();
            $fileName=time().rand(1,1000).'.'.$extension;
            $image->move(public_path("imageId"),$fileName);
            $user->imageId=$fileName;
        }
        $user->save();

    return response()->json([
        'message' => 'User registered successfully',
        'user' => $user,
    ], 201);

    }
    function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        // اذا الايميل والباسورد موجودين في قاعدة البيانات
        if(!Auth::attempt($request->only('email','password'))){
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }
        
        $user=User::where('email',$request->email)->firstOrFail();
        //اذا  الادمن وافق او لا
        if($user->active=="0"){
            return response()->json([
                'message' => 'Your account is not active yet. Please wait for admin approval.',
            ], 403);
        }
        $user['imagePersonal']=url("imagePersonal/".$user->imagePersonal);
        $user['imageId']=url("imageId/".$user->imageId);
        $token=$user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }
    function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }

}

