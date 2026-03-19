<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LargeRequestBody
{
    public function handle(Request $request, Closure $next)
    {
        ini_set('post_max_size', '50M');
        ini_set('upload_max_filesize', '50M');
        ini_set('memory_limit', '256M');

        return $next($request);
    }
}
