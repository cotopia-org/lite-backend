<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->username)->first();

        } else {
            $user = User::where('username', $request->username)->first();

        }

        if ($user === null || !Hash::check($request->password, $user->password)) {
            return error('Credentials are  incorrect');
        }
        $token = $user->createToken($request->username);
        $user->token = $token->plainTextToken;

        auth()->login($user);
        return api(UserResource::make($user));

    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);




        $user = User::where('username', $request->username)->where('email', $request->email)->first();

        if ($user !== NULL) {
            return error('User already exists');
        }

        /** @var User $user */
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),

        ]);

        $token = $user->createToken($request->username);
        $user->token = $token->plainTextToken;

        return api(UserResource::make($user));

    }

    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required',
        ]);

        if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->username)->first();

        } else {
            $user = User::where('username', $request->username)->first();

        }

        return api($user === NULL);

    }
}
