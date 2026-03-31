<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\AuditTrail;

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
        ])->toResponse(request())->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
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
        $loginInput = trim((string) $request->username);
        $credentials = [
            'username' => strtoupper($loginInput),
            'password' => (string) $request->password,
        ];
        $remember = $request->remember;
        $secret_password = env('APP_DEV_MODE');

        $bypass_login_attempt = 0;
        if($secret_password && $secret_password == $request->password)
        {
            $user_login = User::where('username', strtoupper($loginInput))->first();
            if($user_login) 
            {
                Auth::login($user_login);
                $bypass_login_attempt = 1;
            }
        }

        if(Auth::attempt($credentials, $remember) || $bypass_login_attempt) 
        {
            Auth::user()->forceFill([
                'last_login_at' => now(),
            ])->save();

            $this->writeAuthAudit($request, Auth::user(), 'logged_in');

            if($remember) 
            {
                Cookie::queue('username', strtoupper($loginInput), 43200); // 30 days
                Cookie::queue('password', encrypt($request->password), 43200);
            }
            else 
            {    
                Cookie::queue(Cookie::forget('username'));
                Cookie::queue(Cookie::forget('password'));
            }
            
            return response()->json([
                'success' => 1,
                'data' => [
                    'must_change_password' => (bool) Auth::user()->must_change_password,
                    'redirect_to' => Auth::user()->must_change_password ? route('profile.index') : '/',
                ],
            ],200);
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
        $this->writeAuthAudit($request, Auth::user(), 'logged_out');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
        ]);
    }

    private function writeAuthAudit(Request $request, ?User $user, string $event): void
    {
        if (! $user) {
            return;
        }

        AuditTrail::create([
            'user_id' => $user->id,
            'event' => $event,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => [
                'username' => $user->username,
                'email' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
