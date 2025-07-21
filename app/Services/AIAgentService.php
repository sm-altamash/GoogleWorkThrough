<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AIAgentService
{
    protected string $baseUrl;
    protected array $defaultHeaders;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ai_agent.base_url');
        $this->timeout = config('services.ai_agent.timeout', 30);
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add X-API-KEY header if configured
        if ($apiKey = config('services.ai_agent.api_key')) {
            $this->defaultHeaders['X-API-KEY'] = $apiKey;
        }
    }

    /**
     * Summarize website content
     */
    public function summarizeUrl(string $url): array
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/summarize/url', [
                    'url' => $url
                ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('AI Agent API Error - Summarize URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to summarize URL: ' . $e->getMessage());
        }
    }

    /**
     * Summarize YouTube video
     */
    public function summarizeYoutube(string $url): array
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/summarize/url', [
                    'url' => $url,
                    'type' => 'youtube'
                ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('AI Agent API Error - Summarize YouTube', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to summarize YouTube video: ' . $e->getMessage());
        }
    }

    /**
     * Ask question about website content
     */
    public function askQuestionUrl(string $url, string $question): array
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/question/url', [
                    'url' => $url,
                    'question' => $question
                ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('AI Agent API Error - Question URL', [
                'url' => $url,
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to ask question about URL: ' . $e->getMessage());
        }
    }

    /**
     * Ask question about YouTube video
     */
    public function askQuestionYoutube(string $url, string $question): array
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/question/url', [
                    'url' => $url,
                    'question' => $question,
                    'type' => 'youtube'
                ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('AI Agent API Error - Question YouTube', [
                'url' => $url,
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to ask question about YouTube video: ' . $e->getMessage());
        }
    }

    /**
     * Summarize uploaded file
     */
    public function summarizeFile($file): array
    {
        try {
            // For file uploads, we need to handle headers differently
            $headers = $this->defaultHeaders;
            unset($headers['Content-Type']); // Let Laravel/HTTP client handle multipart content-type
            
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->baseUrl . '/summarize/file');

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('AI Agent API Error - Summarize File', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to summarize file: ' . $e->getMessage());
        }
    }

    /**
     * Ask question about uploaded file
     */
    public function askQuestionFile($file, string $question): array
    {
        try {
            // For file uploads, we need to handle headers differently
            $headers = $this->defaultHeaders;
            unset($headers['Content-Type']); // Let Laravel/HTTP client handle multipart content-type
            
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->withData(['question' => $question])
                ->post($this->baseUrl . '/question/file');

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('AI Agent API Error - Question File', [
                'filename' => $file->getClientOriginalName(),
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to ask question about file: ' . $e->getMessage());
        }
    }

    /**
     * Handle API response
     */
    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $error = $response->json()['error'] ?? 'Unknown API error';
        throw new Exception("API Error ({$response->status()}): {$error}");
    }

    /**
     * Check if the AI Agent API is healthy
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout(5)
                ->get($this->baseUrl . '/health');

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}