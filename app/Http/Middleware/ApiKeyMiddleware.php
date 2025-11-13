<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * API Key Middleware
 * 
 * WHY USE THIS?
 * - Protects API from unauthorized access
 * - Simple token-based authentication
 * - No need for OAuth for cron jobs
 * 
 * HOW IT WORKS?
 * - Checks for API key in request header
 * - Compares with key stored in .env
 * - Allows/denies access based on match
 */
class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get API key from request header
        // Header name: X-API-Key
        $apiKey = $request->header('X-API-Key');

        // Get valid API key from environment
        $validApiKey = env('BACKUP_API_KEY');

        // Check if API key is valid
        if (!$apiKey || $apiKey !== $validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid API key.'
            ], 401);
        }

        return $next($request);
    }
}
