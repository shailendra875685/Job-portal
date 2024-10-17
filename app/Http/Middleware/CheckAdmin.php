<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user() == null){
            return redirect()->route('home');

        }

        // echo "Dummy text";
        // dd($request->user());
        if($request->user()->role != 'admin'){

            session()->flash('error','you are not autherized to access this page!');
            return redirect()->route('account.profile');

        }
        return $next($request);
    }
}
