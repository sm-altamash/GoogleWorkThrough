<?php

namespace App\Http\Controllers;

use App\Services\AIAgentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class AIAgentController extends Controller
{
    protected AIAgentService $aiAgentService;

    public function __construct(AIAgentService $aiAgentService)
    {
        $this->aiAgentService = $aiAgentService;
    }

    /**
     * Show the AI Agent dashboard
     */
    public function index()
    {
        return view('admin.agent.index');
    }

    /**
     * Summarize URL content
     */
    public function summarizeUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'type' => 'sometimes|in:website,youtube'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $url = $request->input('url');
            $type = $request->input('type', 'website');

            if ($type === 'youtube' || str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                $result = $this->aiAgentService->summarizeYoutube($url);
            } else {
                $result = $this->aiAgentService->summarizeUrl($url);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ask question about URL content
     */
    public function askQuestionUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'question' => 'required|string|min:3',
            'type' => 'sometimes|in:website,youtube'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $url = $request->input('url');
            $question = $request->input('question');
            $type = $request->input('type', 'website');

            if ($type === 'youtube' || str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                $result = $this->aiAgentService->askQuestionYoutube($url, $question);
            } else {
                $result = $this->aiAgentService->askQuestionUrl($url, $question);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summarize uploaded file
     */
    public function summarizeFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt,pdf,xlsx,xls|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $result = $this->aiAgentService->summarizeFile($file);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ask question about uploaded file
     */
    public function askQuestionFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt,pdf,xlsx,xls|max:10240', // 10MB max
            'question' => 'required|string|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $question = $request->input('question');
            $result = $this->aiAgentService->askQuestionFile($file, $question);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check AI Agent API health
     */
    public function healthCheck(): JsonResponse
    {
        $isHealthy = $this->aiAgentService->healthCheck();

        return response()->json([
            'success' => true,
            'healthy' => $isHealthy,
            'message' => $isHealthy ? 'AI Agent API is healthy' : 'AI Agent API is not responding'
        ]);
    }
}