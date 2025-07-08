<?php

namespace App\Http\Controllers;

use App\Services\GoogleClientService;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarController extends Controller
{
    protected GoogleCalendarService $googleCalendar;
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleCalendarService $googleCalendar, GoogleClientService $googleClientService)
    {
        $this->googleCalendar = $googleCalendar;
        $this->googleClientService = $googleClientService;
        $this->middleware('auth');
    }

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


    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$this->googleClientService->hasValidToken($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected'
                ], 401);
            }
            $events = $this->googleCalendar->listEvents($user, 'primary', 50);
            return response()->json([
                'success' => true,
                'events' => $events,
                'message' => 'Events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching calendar events: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch events: ' . $e->getMessage()
            ], 500);
        }
    }


        public function view()
    {
        return view('admin.calendar.index');
    }


    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable|string|max:2000',
            'timezone' => 'required|string|in:UTC,Asia/Karachi,America/New_York,Europe/London,Asia/Dubai,Asia/Tokyo'
        ]);

        try {
            $user = Auth::user();
            
            if (!$user->google_access_token) {
                return back()->with('error', 'Google Calendar not connected');
            }

            // Convert datetime to proper format
            $startTime = Carbon::parse($request->start_time, $request->timezone)->toISOString();
            $endTime = Carbon::parse($request->end_time, $request->timezone)->toISOString();

            $eventData = [
                'title' => $request->title,
                'description' => $request->description,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $request->timezone
            ];

            $event = $this->googleCalendar->createEvent($user, $eventData);
            
            return back()->with('success', 'Event created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating calendar event: ' . $e->getMessage());
            return back()->with('error', 'Failed to create event: ' . $e->getMessage());
        }
    }

    public function update(Request $request, string $eventId)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable|string|max:2000',
            'timezone' => 'required|string|in:UTC,Asia/Karachi,America/New_York,Europe/London,Asia/Dubai,Asia/Tokyo'
        ]);

        try {
            $user = Auth::user();
            
            if (!$user->google_access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected'
                ], 401);
            }

            // Convert datetime to proper format
            $startTime = Carbon::parse($request->start_time, $request->timezone)->toISOString();
            $endTime = Carbon::parse($request->end_time, $request->timezone)->toISOString();

            $eventData = [
                'title' => $request->title,
                'description' => $request->description,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $request->timezone
            ];

            $event = $this->googleCalendar->updateEvent($user, $eventId, $eventData);
            
            return response()->json([
                'success' => true,
                'event' => $event,
                'message' => 'Event updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating calendar event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $eventId)
    {
        try {
            $user = Auth::user();
            
            if (!$user->google_access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected'
                ], 401);
            }

            $this->googleCalendar->deleteEvent($user, $eventId);
            
            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting calendar event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show(string $eventId)
    {
        try {
            $user = Auth::user();
            
            if (!$user->google_access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected'
                ], 401);
            }

            $event = $this->googleCalendar->getEvent($user, $eventId);
            
            return response()->json([
                'success' => true,
                'event' => $event,
                'message' => 'Event retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching calendar event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch event: ' . $e->getMessage()
            ], 500);
        }
    }
}