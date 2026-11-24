<?php

namespace Kyledoesdev\Essentials\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsDeveloper
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(auth()->user()?->is_dev, 403);

        return $next($request);
    }
}
