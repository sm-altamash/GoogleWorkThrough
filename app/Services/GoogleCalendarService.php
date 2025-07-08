<?php

namespace App\Services;

use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use App\Models\User;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected GoogleClientService $googleClient;

    public function __construct(GoogleClientService $googleClient)
    {
        $this->googleClient = $googleClient;
    }

    public function getCalendarService(User $user): Calendar
    {
        $client = $this->googleClient->getClientForUser($user);
        return new Calendar($client);
    }

    public function listEvents(User $user, ?string $calendarId = 'primary', int $maxResults = 10): array
    {
        $service = $this->getCalendarService($user);
        
        $optParams = [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => Carbon::now()->toISOString(),
        ];

        $results = $service->events->listEvents($calendarId, $optParams);
        
        return $results->getItems();
    }

    public function createEvent(User $user, array $eventData, ?string $calendarId = 'primary'): Event
    {
        $service = $this->getCalendarService($user);
        
        $event = new Event([
            'summary' => $eventData['title'],
            'description' => $eventData['description'] ?? '',
            'start' => [
                'dateTime' => $eventData['start_time'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ],
            'end' => [
                'dateTime' => $eventData['end_time'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ],
            'attendees' => $eventData['attendees'] ?? [],
        ]);

        return $service->events->insert($calendarId, $event);
    }

    public function updateEvent(User $user, string $eventId, array $eventData, ?string $calendarId = 'primary'): Event
    {
        $service = $this->getCalendarService($user);
        
        $event = $service->events->get($calendarId, $eventId);
        
        if (isset($eventData['title'])) {
            $event->setSummary($eventData['title']);
        }
        
        if (isset($eventData['description'])) {
            $event->setDescription($eventData['description']);
        }
        
        if (isset($eventData['start_time'])) {
            $event->setStart([
                'dateTime' => $eventData['start_time'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ]);
        }
        
        if (isset($eventData['end_time'])) {
            $event->setEnd([
                'dateTime' => $eventData['end_time'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ]);
        }

        return $service->events->update($calendarId, $eventId, $event);
    }

    public function deleteEvent(User $user, string $eventId, ?string $calendarId = 'primary'): void
    {
        $service = $this->getCalendarService($user);
        $service->events->delete($calendarId, $eventId);
    }

    public function getEvent(User $user, string $eventId, ?string $calendarId = 'primary'): Event
    {
        $service = $this->getCalendarService($user);
        return $service->events->get($calendarId, $eventId);
    }

    public function listCalendars(User $user): array
    {
        $service = $this->getCalendarService($user);
        $results = $service->calendarList->listCalendarList();
        
        return $results->getItems();
    }
}

