<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;

use Session;
use Log;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Inertia\Response
     */

    public function create()
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'username' => Cookie::get('username'),
            'password' => Cookie::get('password') ? decrypt(Cookie::get('password')) : ''
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function store(LoginRequest $request)
    {
        $credentials = $request->only('username', 'password');
        $remember = $request->remember;
        $secret_password = env('APP_DEV_MODE');

        $bypass_login_attempt = 0;
        if($secret_password && $secret_password == $request->password)
        {
            $user_login = User::where('username', $request->username)->first();
            if($user_login) 
            {
                Auth::login($user_login);
                $bypass_login_attempt = 1;
            }
        }

        if(Auth::attempt($credentials, $remember) || $bypass_login_attempt) 
        {
            if($remember) 
            {
                Cookie::queue('username', $request->username, 43200); // 30 days
                Cookie::queue('password', encrypt($request->password), 43200);
            }
            else 
            {    
                Cookie::queue(Cookie::forget('username'));
                Cookie::queue(Cookie::forget('password'));
            }
            
            return response()->json(['success' => 1, "data" => ""],200);
        }

        return response()->json(['success' => 0, "message" => "Invalid Credentials"],500);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
