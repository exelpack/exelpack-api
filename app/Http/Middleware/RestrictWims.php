<?php

namespace App\Http\Middleware;

use Closure;

class RestrictWims
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(auth()->user()->wims_access == 1){
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'],401);
    }
}
