<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SanctumAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if ($sanctumToken = $request->header('X-Sanctum-Token')) {
                // Remove 'Bearer ' if present
                $token = str_replace('Bearer ', '', $sanctumToken);
                
                // Store original Authorization header
                $originalAuth = $request->header('Authorization');
                
                // Set Sanctum token for authentication
                $request->headers->set('Authorization', 'Bearer ' . $token);
                
                if (!Auth::guard('sanctum')->check()) {
                    Log::channel('slack')->alert('Sanctum authentication failed with token: ' . $token);
                    return response()->json([
                        'message' => 'Unauthorized',
                        'error' => 'Invalid Sanctum token'
                    ], 401);
                }
                
                // Restore original Authorization header for Passport
                $request->headers->set('Authorization', $originalAuth);
            } else {
                return response()->json([
                    'message' => 'Unauthorized',
                    'error' => 'Missing X-Sanctum-Token header'
                ], 401);
            }

            return $next($request);
        } catch (\Exception $e) {
            Log::channel('slack')->alert('Sanctum authentication failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'message' => 'Authentication error',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
} 