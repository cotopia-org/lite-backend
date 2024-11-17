<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSocketIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {


        if (auth()->check()) {
            if (auth()->user()->socket_id === $request->header('socket-id')) {
                return $next($request);

            }


        }

        return error('Deactivated');
    }
}
