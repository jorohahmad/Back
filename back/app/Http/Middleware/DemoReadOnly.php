<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoReadOnly
{
    /**
     * في الوضع التجريبي: الحساب التجريبي يستطيع مشاهدة كل شيء لكن لا يستطيع تعديل البيانات
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('demo.enabled')) {
            return $next($request);
        }

        if ($request->is(...config('demo.blocked_public_routes'))) {
            return $this->blocked();
        }

        $user = $request->user('sanctum');
        if (!$user || $user->email !== config('demo.email')) {
            return $next($request);
        }

        if ($request->is(...config('demo.allowed_write_routes'))) {
            return $next($request);
        }

        $isWrite = !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS']);
        if ($isWrite || $request->is(...config('demo.blocked_get_routes'))) {
            return $this->blocked();
        }

        return $next($request);
    }

    private function blocked(): Response
    {
        return response()->json([
            'status'  => 'error',
            'demo'    => true,
            'message' => 'This action is disabled in the demo version (read-only).',
        ], 403);
    }
}
