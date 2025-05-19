<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = config('services.subscription_api.key');

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key is missing'
            ], 400);
        }

        $response = Http::post(config('services.subscription_api.url') . 'api/check-subscription', [
            'api_key' => $apiKey
        ]);


        if ($response->status() !== 200 || $response->json('status') === 'expired') {
            return redirect()->to(config('services.subscription_api.redirect'));
        }

        return $next($request);
    }
}
