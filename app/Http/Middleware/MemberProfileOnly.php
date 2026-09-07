<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberProfileOnly
{
    /**
     * Members may view only their own profile. All application data routes are
     * reserved for church staff roles.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole(User::ROLE_MEMBER)
            && ! $request->routeIs('profile*')) {
            return redirect()->route('profile');
        }

        return $next($request);
    }
}
