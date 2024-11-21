<?php

namespace App\Http\Middleware;

use App\Utilities\Constants;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserOnlineMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response {


        if (auth()->check()) {
            $user = auth()->user();

            if ($user->status !== Constants::ONLINE || $user->room_id === NULL) {
                abort(400, 'Not Online');
            }
        }
        return $next($request);
    }
}
