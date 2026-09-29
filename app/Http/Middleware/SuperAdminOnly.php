<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Allow the request only for a logged-in super admin.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (empty(session()->get('super_admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can perform this action.',
            ], 403);
        }
        return $next($request);
    }
}
