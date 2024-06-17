<?php

namespace App\Http\Middleware;

use App\Traits\GeneralTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use GeneralTrait;

    public function handle(Request $request, Closure $next,$role): Response
    {
        try {
            $user = auth('api')->user();
            if (!$user->hasRole($role)) {
                return $this->returnError('512', "you dont have the right role");
            }
            else{
                return $next($request);
            }

        }catch (\Exception $e) {
            return $this->returnError('511', 'Unauthorized User');
        }
    }
}
