<?php

namespace App\Http\Controllers;

use App\Services\GoogleFormsService;
use App\Services\GoogleClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GoogleFormsController extends Controller
{
    protected GoogleFormsService $googleFormsService;
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleFormsService $googleFormsService, GoogleClientService $googleClientService)
    {
        $this->googleFormsService = $googleFormsService;
        $this->googleClientService = $googleClientService;
        $this->middleware('auth');
    }

    
    //  Check if user has Google connection before proceeding
    public function checkConnection()
    {
        try {
            $user = Auth::user();
            // Use GoogleClientService to validate token
            $isConnected = $this->googleClientService->hasValidToken($user);
            return response()->json([
                'success' => true,
                'connected' => $isConnected,
                'message' => $isConnected ? 'Google Calendar connected' : 'Google Calendar not connected'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'connected' => false,
                'message' => 'Error checking connection: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Display the forms management page
    public function index()
    {
        $user = Auth::user();
        
        // Check if Google is connected
        $googleConnected = $this->googleClientService->hasValidToken($user);
        
        return view('admin.forms.index', compact('googleConnected'));
    }

    
    //  Show form to create a new Google Form
    public function create()
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        return view('admin.forms.index');
    }

    //   Store a new Google Form
    public function store(Request $request): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $form = $this->googleFormsService->createForm(
                $user,
                $request->title,
                $request->description ?? ''
            );

            Log::info('Google Form created via controller', [
                'user_id' => $user->id,
                'form_id' => $form['form_id'],
                'title' => $request->title
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google Form created successfully!',
                'data' => $form
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating Google Form via controller', [
                'user_id' => Auth::id(),
                'title' => $request->title,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Google Form: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Show a specific Google Form
    public function show(string $formId): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        try {
            $user = Auth::user();
            $form = $this->googleFormsService->getForm($user, $formId);

            return response()->json([
                'success' => true,
                'data' => $form
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching Google Form via controller', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Google Form: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Show form to edit Google Form
    public function edit(string $formId)
    {
        if ($response = $this->checkGoogleConnection()) {
            // For view responses, redirect to connect Google
            return redirect()->route('google.redirect')
                ->with('error', 'Please connect your Google account to manage forms.');
        }

        try {
            $user = Auth::user();
            $form = $this->googleFormsService->getForm($user, $formId);

            return view('admin.forms.index', compact('form', 'formId'));

        } catch (\Exception $e) {
            Log::error('Error fetching form for edit', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('forms.index')
                ->with('error', 'Failed to load form for editing.');
        }
    }

    
    //   Update Google Form info
    public function update(Request $request, string $formId): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $result = $this->googleFormsService->updateFormInfo(
                $user,
                $formId,
                $request->title,
                $request->description ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Google Form updated successfully!',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating Google Form via controller', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Google Form: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Add a text question to the form
    public function addTextQuestion(Request $request, string $formId): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $result = $this->googleFormsService->addTextQuestion(
                $user,
                $formId,
                $request->title,
                $request->boolean('required', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'Text question added successfully!',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error adding text question via controller', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add text question: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Add a multiple choice question to the form
    public function addMultipleChoiceQuestion(Request $request, string $formId): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string|max:255',
            'required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $result = $this->googleFormsService->addMultipleChoiceQuestion(
                $user,
                $formId,
                $request->title,
                $request->options,
                $request->boolean('required', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'Multiple choice question added successfully!',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error adding multiple choice question via controller', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add multiple choice question: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Get form responses
    public function responses(string $formId): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        try {
            $user = Auth::user();
            $responses = $this->googleFormsService->getFormResponses($user, $formId);

            return response()->json([
                'success' => true,
                'data' => $responses
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching form responses via controller', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch form responses: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Show form responses page
    public function showResponses(string $formId)
    {
        if ($response = $this->checkGoogleConnection()) {
            // For view responses, redirect to connect Google
            return redirect()->route('google.redirect')
                ->with('error', 'Please connect your Google account to view form responses.');
        }

        try {
            $user = Auth::user();
            $form = $this->googleFormsService->getForm($user, $formId);
            $responses = $this->googleFormsService->getFormResponses($user, $formId);

            return view('admin.forms.index', compact('form', 'responses', 'formId'));

        } catch (\Exception $e) {
            Log::error('Error fetching form responses for view', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('forms.index')
                ->with('error', 'Failed to load form responses.');
        }
    }

    
    //   Delete a Google Form
    public function destroy(string $formId): JsonResponse
    {
        if ($response = $this->checkGoogleConnection()) {
            return $response;
        }

        try {
            $user = Auth::user();
            $result = $this->googleFormsService->deleteForm($user, $formId);

            return response()->json([
                'success' => true,
                'message' => 'Form deletion requested. Please delete the form manually from Google Drive if needed.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting Google Form via controller', [
                'user_id' => Auth::id(),
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Google Form: ' . $e->getMessage()
            ], 500);
        }
    }

    
    //   Get Google connection status
    public function connectionStatus(): JsonResponse
    {
        $user = Auth::user();
        $connected = $this->googleClientService->hasValidToken($user);

        return response()->json([
            'success' => true,
            'data' => [
                'connected' => $connected,
                'connect_url' => route('google.redirect')
            ]
        ]);
    }
}