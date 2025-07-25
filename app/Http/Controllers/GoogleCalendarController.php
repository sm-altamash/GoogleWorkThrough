<?php

namespace App\Http\Controllers;

use App\Services\GoogleClientService;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;


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



    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'start_time' => 'required|date_format:Y-m-d\TH:i',
            'end_time' => 'required|date_format:Y-m-d\TH:i|after:start_time',
            'description' => 'nullable|string|max:2000',
            'timezone' => 'required|string|in:UTC,Asia/Karachi,America/New_York,Europe/London,Asia/Dubai,Asia/Tokyo',
            'create_meet' => 'boolean', // New field
        ]);

        try {
            $user = Auth::user();
            if (!$user || !$user->googleToken || !$user->googleToken->access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected or user not authenticated',
                ], 400);
            }

            // Convert to UTC ISO8601
            $startTime = Carbon::createFromFormat('Y-m-d\TH:i', $validated['start_time'], $validated['timezone'])
                ->setTimezone('UTC')->toIso8601String();
            $endTime = Carbon::createFromFormat('Y-m-d\TH:i', $validated['end_time'], $validated['timezone'])
                ->setTimezone('UTC')->toIso8601String();

            $eventData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $validated['timezone'],
                'create_meet' => $request->boolean('create_meet', false),
            ];

            $event = $this->googleCalendar->createEvent($user, $eventData);

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'event' => $event,
                'meet_link' => $event->getHangoutLink() ?? null, // Optional
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating calendar event: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $eventId)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'start_time' => 'required|date_format:Y-m-d\TH:i',
            'end_time' => 'required|date_format:Y-m-d\TH:i|after:start_time',
            'description' => 'nullable|string|max:2000',
            'timezone' => 'required|string|in:UTC,Asia/Karachi,America/New_York,Europe/London,Asia/Dubai,Asia/Tokyo',
            'create_meet' => 'boolean' // Add this line

        ]);

        try {
            $user = Auth::user();
            if (!$user || !$user->googleToken || !$user->googleToken->access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected or user not authenticated',
                ], 400);
            }

            // Convert datetime to proper format
            $startTime = Carbon::createFromFormat('Y-m-d\TH:i', $request->start_time, $request->timezone)
                ->setTimezone('UTC')->toIso8601String();
            $endTime = Carbon::createFromFormat('Y-m-d\TH:i', $request->end_time, $request->timezone)
                ->setTimezone('UTC')->toIso8601String();

            $eventData = [
                'title' => $request->title,
                'description' => $request->description,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $request->timezone,
                'create_meet' => $request->boolean('create_meet', false), // Pass this
            ];

            $event = $this->googleCalendar->updateEvent($user, $eventId, $eventData);
            return response()->json([
                'success' => true,
                'event' => $event,
                'meet_link' => $event->getHangoutLink() ?? null,
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
            if (!$user || !$user->googleToken || !$user->googleToken->access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected or user not authenticated',
                ], 400);
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
            if (!$user || !$user->googleToken || !$user->googleToken->access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not connected or user not authenticated',
                ], 400);
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