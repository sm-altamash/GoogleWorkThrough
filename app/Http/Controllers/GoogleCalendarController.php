<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleCalendarService;
use App\Services\GoogleClientService;
use Illuminate\Support\Facades\Auth;

class GoogleCalendarController extends Controller
{
    protected GoogleCalendarService $calendarService;
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleCalendarService $calendarService, GoogleClientService $googleClientService)
    {
        $this->calendarService = $calendarService;
        $this->googleClientService = $googleClientService;
    }



        public function view()
        {
            $isConnected = Session::has('google_token');

            return view('calendar.index', [
                'isConnected' => $isConnected
            ]);
        }



    public function index()
    {
        try {
            $user = Auth::user();
            
            // Check if user has a valid Google token
            if (!$this->googleClientService->hasValidToken($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no Google token',
                    'auth_required' => true,
                    'auth_url' => url('/auth/google') // URL to your Google auth endpoint
                ], 401);
            }
            
            $events = $this->calendarService->listEvents($user);
            
            return response()->json([
                'success' => true,
                'events' => $events
            ]);


        } catch (\Exception $e) {
            // Check if it's a token expiration error
            if (strpos($e->getMessage(), 'unauthorized') !== false || 
                strpos($e->getMessage(), 'invalid_token') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google token expired or invalid',
                    'auth_required' => true,
                    'auth_url' => url('/auth/google')
                ], 401);
            }
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'timezone' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check if user has a valid Google token
            if (!$this->googleClientService->hasValidToken($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no Google token',
                    'auth_required' => true,
                    'auth_url' => url('/auth/google')
                ], 401);
            }
            
            $event = $this->calendarService->createEvent($user, $request->all());
            
            return response()->json([
                'success' => true,
                'event' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $eventId)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'timezone' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check if user has a valid Google token
            if (!$this->googleClientService->hasValidToken($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no Google token',
                    'auth_required' => true,
                    'auth_url' => url('/auth/google')
                ], 401);
            }
            
            $event = $this->calendarService->updateEvent($user, $eventId, $request->all());
            
            return response()->json([
                'success' => true,
                'event' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $eventId)
    {
        try {
            $user = Auth::user();
            
            // Check if user has a valid Google token
            if (!$this->googleClientService->hasValidToken($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no Google token',
                    'auth_required' => true,
                    'auth_url' => url('/auth/google')
                ], 401);
            }
            
            $this->calendarService->deleteEvent($user, $eventId);
            
            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has connected their Google account
     */
    public function checkConnection()
    {
        try {
            $user = Auth::user();
            $hasToken = $this->googleClientService->hasValidToken($user);
            
            return response()->json([
                'success' => true,
                'connected' => $hasToken,
                'auth_url' => $hasToken ? null : url('/auth/google')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function callback()
    {
        try {
            $this->calendarService->handleCallback(); 

            return redirect()->route('calendar.view')->with('success', 'Google account connected!');
        } catch (\Exception $e) {
            return redirect()->route('calendar.view')->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }

    public function connect()
    {
        return $this->calendarService->connect();
    }

    public function disconnect()
    {
        Session::forget('google_token');

        return response()->json(['success' => true]);
    }
}