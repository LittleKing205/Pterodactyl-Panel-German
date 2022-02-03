<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;

class SSO
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        $authToken = $request->input("authToken", "empty");
        if ($authToken != "empty") {
            $tokenData = DB::table('sso_token')->where('token', $authToken)->first();
            if (isset($tokenData)) {
                Auth::loginUsingId($tokenData->user_id);
                DB::table('sso_token')->where('id', $tokenData->id)->delete();
            } else {
                throw new AuthenticationException();
            }
        }

        return $next($request);
    }
}
