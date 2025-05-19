<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure() && config('app.env') === 'production') {
            abort(403, 'Secure connection required');
        }

        $domain = config('services.subscription_api.domain');
        $apiSecret = config('services.subscription_api.secret');

        if (!$apiSecret || !$domain) {
            return response()->json([
                'status' => 'error',
                'message' => 'System configuration error'
            ], 500)->withHeaders($this->securityHeaders());
        }

        $timestamp = now()->timestamp;
        $nonce = Str::uuid()->toString();

        $signature = hash_hmac(
            'sha256',
            $timestamp . $domain . $nonce,
            $apiSecret
        );
        $baseUrl = config('services.subscription_api.url');
        $endpoint = rtrim($baseUrl, '/') . '/api/check-subscription';

        try {
            $response = Http::withHeaders([
                'X-API-Signature' => $signature,
                'X-API-Timestamp' => $timestamp,
                'X-API-Nonce' => $nonce,
                'X-API-Domain' => $domain
            ])
                ->timeout(3)
                ->retry(2, 100)
                ->post($endpoint, [
                    'domain' => $domain,
                    'timestamp' => $timestamp
                ]);

            return $this->handleApiResponse($response, $request, $next);
        } catch (ConnectionException $e) {
            return $this->handleFallbackVerification($request, $next);
        }
    }

    private function handleApiResponse($response, $request, $next)
    {
        $status = $response->status();

        if ($status === 200 && $response->json('status') === 'active') {
            $this->cacheSubscriptionStatus();
            return $next($request)->withHeaders($this->securityHeaders());
        }

        if ($response->json('status') === 'expired') {
            $responseData = $response->json();
            if (
                isset($responseData['status']) && $responseData['status'] === 'expired' &&
                isset($responseData['message']) && $responseData['message'] === 'Subscription expired'
            ) {

                $redirectUrl = isset($responseData['redirect_url']) ?
                    $responseData['redirect_url'] :
                    config('services.subscription_api.redirect_url');

                return redirect()->to($redirectUrl);
            }

            return redirect()->to(config('services.subscription_api.redirect_url'));
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Service temporarily unavailable'
        ], 503)->withHeaders($this->securityHeaders());
    }

    private function securityHeaders()
    {
        return [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            // 'Content-Security-Policy' => "default-src 'self'"
        ];
    }

    private function handleFallbackVerification($request, $next)
    {
        $domain = config('services.subscription_api.domain');
        $lastVerified = Cache::get('subscription_status_' . md5($domain));

        if ($lastVerified && $lastVerified['expires_at'] > now()) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Service unavailable'
        ], 503)->withHeaders($this->securityHeaders());
    }

    private function cacheSubscriptionStatus()
    {
        $domain = config('services.subscription_api.domain');
        Cache::put(
            'subscription_status_' . md5($domain),
            ['expires_at' => now()->addMinutes(5)],
            300
        );
    }
}
