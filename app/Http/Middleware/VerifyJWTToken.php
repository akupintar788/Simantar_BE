<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth; // Import JWTAuth
use Illuminate\Support\Facades\Response; // Gunakan facade Response

class VerifyJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Cek token dari cookie
            $token = $request->cookie('jwt');
            JWTAuth::setToken($token)->authenticate();
        } catch (\Exception $e) {
            // Menggunakan facade Response untuk return
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}
