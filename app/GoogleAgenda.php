<?php

namespace App;

// TODO: import google-api

class GoogleAgenda
{
    private static function getClient()
    {
        // FIXME: change credentials

        $client = new Google_Client();
        $client->setApplicationName('Google Calendar API TEST');
        $client->setAuthConfig(__DIR__ . '/../LSF-ISO-999d69173e47.json');
        $client->useApplicationDefaultCredentials();
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        return $client;
    }

    public static function ListAgenda()
    {
        $client = GoogleAgenda::getClient();
        $service = new Google_Service_Calendar($client);
        $calendarList = $service->calendarList->listCalendarList();
        //print_r($calendarList);
    }

    public static function ListEvents($calendarId)
    {
        // Get the API client and construct the service object.
        $client = GoogleAgenda::getClient();
        $service = new Google_Service_Calendar($client);
        // Print the next 10 events on the user's calendar.
        //$calendarId = 'primary';
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();

        if (empty($events)) {
            print "No upcoming events found.\n";
        } else {
            print "Upcoming events:\n";
            foreach ($events as $event) {
                $start = $event->start->dateTime;
                if (empty($start)) {
                    $start = $event->start->date;
                }
                printf("%s (%s)\n", $event->getSummary(), $start);
            }
        }
    }

    public static function createEvent($calendarId, $idrdv, $summary, $description, $start, $end, $location)
    {
        // USEFUL LINK - https://stackoverflow.com/questions/50656151/adding-an-event-to-google-calendar-using-php
        $client = GoogleAgenda::getClient();
        $service = new Google_Service_Calendar($client);

        $event = new Google_Service_Calendar_Event(array(
            'summary' => $summary,
            'location' => $location,
            'description' => $description,
            'start' => array(
                //'dateTime' => '2018-06-02T09:00:00-07:00'
                'dateTime' => date('Y-m-d\TH:i:s', $start),
                'timeZone' => 'Europe/Paris'
            ),
            'end' => array(
                'dateTime' => date('Y-m-d\TH:i:s', $end),
                'timeZone' => 'Europe/Paris'
            )
        ));
        $event = $service->events->insert($calendarId, $event);
        return $event->id;
    }

    public static function deleteEvent($calendarId, $idEvent)
    {
        $client = GoogleAgenda::getClient();
        $service = new Google_Service_Calendar($client);
        $service->events->delete($calendarId, $idEvent);
        return true;
    }
}
