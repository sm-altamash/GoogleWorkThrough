<?php

namespace App\Http\Controllers;

use App\Services\GoogleGmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class GoogleGmailController extends Controller
{
    protected GoogleGmailService $gmailService;

    public function __construct(GoogleGmailService $gmailService)
    {
        $this->gmailService = $gmailService;
        $this->middleware('auth');
    }

    public function view()
    {
        try {
            $user = Auth::user();
            $isConnected = $this->gmailService->isConnected($user);

            return view('admin.gmail.index', [
                'isConnected' => $isConnected
            ]);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to load Gmail interface: ' . $e->getMessage());
        }
    }

    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $messages = $this->gmailService->listEmails($user);
            
            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch emails: ' . $e->getMessage()
            ], 500);
        }
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'sometimes|file|max:10240' // 10MB max per file
        ]);

        try {
            $user = Auth::user();
            $options = [];

            // Handle attachments if present
            if ($request->hasFile('attachments')) {
                $options['attachments'] = [];
                foreach ($request->file('attachments') as $file) {
                    $options['attachments'][] = [
                        'name' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                        'content' => file_get_contents($file->getRealPath())
                    ];
                }
            }

            $message = $this->gmailService->sendEmail(
                $user,
                $request->input('to'),
                $request->input('subject'),
                $request->input('body'),
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully',
                'messageId' => $message->getId()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createDraft(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'sometimes|file|max:10240'
        ]);

        try {
            $user = Auth::user();
            $options = [];

            if ($request->hasFile('attachments')) {
                $options['attachments'] = [];
                foreach ($request->file('attachments') as $file) {
                    $options['attachments'][] = [
                        'name' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                        'content' => file_get_contents($file->getRealPath())
                    ];
                }
            }

            $draft = $this->gmailService->createDraft(
                $user,
                $request->input('to'),
                $request->input('subject'),
                $request->input('body'),
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Draft created successfully',
                'draftId' => $draft->getId()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create draft: ' . $e->getMessage()
            ], 500);
        }
    }
}