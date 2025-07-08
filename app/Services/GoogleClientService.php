<?php

namespace App\Services;

use Google\Client;
use App\Models\GoogleToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleClientService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function getAccessToken(string $code): array
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function getClientForUser(User $user): Client
    {
        $token = $user->googleToken;
        
        if (!$token) {
            throw new \Exception('User has no Google token');
        }

        if ($token->isExpired() || $token->isExpiringSoon()) {
            $token = $this->refreshToken($token);
        }

        $this->client->setAccessToken($token->access_token);
        
        return $this->client;
    }

    public function storeToken(User $user, array $tokenData): GoogleToken
    {
        return GoogleToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => Carbon::now()->addSeconds($tokenData['expires_in']),
            ]
        );
    }

    /**
     * Check if user has a valid Google token
     */
    public function hasValidToken(User $user): bool
    {
        try {
            $token = $user->googleToken;
            
            // Check if user has a Google token record
            if (!$token) {
                return false;
            }
            
            // Check if token is expired
            if ($token->isExpired() || $token->isExpiringSoon()) {
                // Try to refresh token if refresh token exists
                if ($token->refresh_token) {
                    try {
                        $this->refreshToken($token);
                        return true;
                    } catch (\Exception $e) {
                        Log::warning('Failed to refresh Google token:', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                        return false;
                    }
                }
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error checking Google token validity:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Refresh the Google access token using GoogleToken model
     */
    protected function refreshToken(GoogleToken $token): GoogleToken
    {
        if (!$token->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $this->client->refreshToken($token->refresh_token);
        $newTokenData = $this->client->getAccessToken();

        if (!$newTokenData) {
            throw new \Exception('Failed to refresh token');
        }

        return $this->storeToken($token->user, $newTokenData);
    }


    public function clearToken(User $user): void
    {
        if ($user->googleToken) {
            $user->googleToken->delete();
        }
    }
}