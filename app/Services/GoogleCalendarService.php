<?php

namespace App\Services;

use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;
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

        // Generate Google Meet link if requested
        if (!empty($eventData['create_meet'])) {
            $conferenceData = new ConferenceData();
            $createRequest = new CreateConferenceRequest();
            $createRequest->setRequestId('req_' . uniqid());
            $createRequest->setConferenceSolutionKey(new ConferenceSolutionKey([
                'type' => 'hangoutsMeet'
            ]));
            $conferenceData->setCreateRequest($createRequest);
            $event->setConferenceData($conferenceData);
        }

        // Insert event with conferenceDataVersion to ensure Meet link generation
        $options = !empty($eventData['create_meet']) ? ['conferenceDataVersion' => 1] : [];
        return $service->events->insert($calendarId, $event, $options);
    }

    public function updateEvent(User $user, string $eventId, array $eventData, ?string $calendarId = 'primary'): Event
    {
        $service = $this->getCalendarService($user);
        $event = $service->events->get($calendarId, $eventId);

        // Update basic fields
        if (isset($eventData['title'])) $event->setSummary($eventData['title']);
        if (isset($eventData['description'])) $event->setDescription($eventData['description']);
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

        // Handle Google Meet link
        if (isset($eventData['create_meet'])) {
            $createMeet = $eventData['create_meet'];
            
            if ($createMeet && !$event->getConferenceData()) {
                // Create new Meet link
                $conferenceData = new ConferenceData();
                $createRequest = new CreateConferenceRequest();
                $createRequest->setRequestId('req_' . uniqid());
                $createRequest->setConferenceSolutionKey(new ConferenceSolutionKey([
                    'type' => 'hangoutsMeet'
                ]));
                $conferenceData->setCreateRequest($createRequest);
                $event->setConferenceData($conferenceData);
            } elseif (!$createMeet && $event->getConferenceData()) {
                // Remove Meet link (optional - Google may not allow this)
                $event->setConferenceData(null);
            }
        }

        $options = !empty($eventData['create_meet']) ? ['conferenceDataVersion' => 1] : [];
        return $service->events->update($calendarId, $eventId, $event, $options);
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
    

    public function isConnected(User $user): bool
    {
        try {
            $client = $this->googleClient->getClientForUser($user); 

            if (!$client->isAccessTokenExpired()) {
                // Try calling a simple endpoint to test connection
                $service = new Calendar($client);
                $service->calendarList->listCalendarList();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }


}

