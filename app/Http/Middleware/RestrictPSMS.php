<?php

namespace App\Http\Middleware;

use Closure;

class RestrictPSMS
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
      if(auth()->user()->psms_access == 1){
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'],401);
    }
}
