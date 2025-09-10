<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        
        if ($role === 'super_admin' && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($role === 'staff' && !$user->isStaff()) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($role === 'admin_pusat' && $user->role !== 'admin_pusat') {
            abort(403, 'Unauthorized action.');
        }
        
        if ($role === 'kasir' && $user->role !== 'kasir') {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
