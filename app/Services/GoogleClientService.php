<?php

namespace App\Services;

use Google\Client;
use App\Models\GoogleToken;
use App\Models\User;
use Carbon\Carbon;

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

    protected function refreshToken(GoogleToken $token): GoogleToken
    {
        if (!$token->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $this->client->refreshToken($token->refresh_token);
        $newToken = $this->client->getAccessToken();

        return $this->storeToken($token->user, $newToken);
    }
}