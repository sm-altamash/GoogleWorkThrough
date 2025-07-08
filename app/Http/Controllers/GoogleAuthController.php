<?php

namespace App\Http\Controllers;

use App\Services\GoogleClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class GoogleAuthController extends Controller
{
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }

    public function redirect(): JsonResponse
    {
        $authUrl = $this->googleClientService->getAuthUrl();
        
        return response()->json([
            'success' => true,
            'auth_url' => $authUrl
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        try {
            $code = $request->get('code');
            
            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code not provided'
                ], 400);
            }

            $tokenData = $this->googleClientService->getAccessToken($code);
            $user = Auth::user();
            
            $this->googleClientService->storeToken($user, $tokenData);

            return response()->json([
                'success' => true,
                'message' => 'Google account connected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}