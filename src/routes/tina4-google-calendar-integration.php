<?php

use Tina4\Get;
use Tina4\Post;
use Tina4\Response;
use Tina4\Request;
use function Tina4\redirect;
use function Tina4\renderTemplate;

Get::add("/", function (Response $response) {

    redirect("/google/landing");
});

Get::add("/google/landing", function (Response $response) {

    return $response(\Tina4\renderTemplate("google-calendar-integration/landing.twig"),
                    HTTP_OK, TEXT_HTML);
});

/**
 * Get authorization for User Google Account
 */
Get::add("/google/calendar/integration/{authScope}/{linkKey}/{linkValue}", function ($authScope, $linkKey, $linkValue, Response $response) {

    $googleAuth = new GoogleCalendarAuth();

    $clientInfo = [
        $linkKey => $linkValue
    ];

    switch($authScope){
        case "calendar":
            $authUrl = "https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events";
            break;
        case "contacts":
            $authUrl = "https://www.googleapis.com/auth/contacts.other.readonly";
            break;
        default:
            $authUrl = "https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/contacts.other.readonly";
            break;
    }

    $authUri = $googleAuth->getOAuthUri(serialize($clientInfo), $authUrl);

    \Tina4\redirect($authUri);

//    return $response(\Tina4\renderTemplate("google-calendar-integration/google-calendar-integration-oauth.twig",
//                                            ["authUri" => $authUri]),
//                    HTTP_OK, TEXT_HTML);
});

Get::add("/google/calendar/get-access-token", function (Response $response, Request $request) {

    $googleCalendarAccessObj = (new GoogleCalendarAuth())->createAccessToken($request->params["code"]);

    $googleCalendarSettings = new GoogleCalendarSettings();

    foreach (unserialize(base64_decode($request->params["state"])) as $key => $value){

        $description  = $googleCalendarSettings->getAuthScopeDescription($googleCalendarAccessObj["body"]["scope"]);

        $googleCalendarSettings->saveSettingsInformation($key, $value, "code", $request->params["code"], $description);

        $googleCalendarSettings->saveSettingsInformation($key, $value, "access_token",
                                                         $googleCalendarAccessObj["body"]["access_token"], $description);

        $googleCalendarSettings->saveSettingsInformation($key, $value, "refresh_token",
                                                         $googleCalendarAccessObj["body"]["refresh_token"], $description);

    }

    if(isset( $_COOKIE["authBeforeEvent"] ))
    {
        setcookie("authBeforeEvent","",time() - 3600, "/");
        redirect($_COOKIE["authBeforeEvent"]);
    }

    return $response("Access");
});


/**
 * Get route for creating an Event - displays a form to create an event
 */
Get::add("/google/calendar/create-event/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    /**
     * if there is no accessToken saved in tina4_integration for with $linkKey and $linkValue
     */
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"], time() + (86400 * 30), "/");

        $link = "/google/calendar/integration/all/{$linkKey}/{$linkValue}";

        return $response(renderTemplate("/google-calendar-integration/components/auth-screen.twig", ["authLink" => $link]),
                        HTTP_OK, TEXT_HTML);
    }

    if( $contactList = (new GoogleCalendarIntegration())->getContactList($linkKey, $linkValue, $accessToken) ){
        $promptLinkContacts = false;
    }
    else{
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"],time() + (86400 * 30), "/");
        $promptLinkContacts = true;
    }

    if( !( $calendars = (new GoogleCalendarIntegration())->getCalendarList($linkKey, $linkValue, $accessToken) ) ) {
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"], time() + (86400 * 30), "/");

        $link = "/google/calendar/integration/all/{$linkKey}/{$linkValue}";

        return $response(renderTemplate("/google-calendar-integration/components/auth-screen.twig", ["authLink" => $link]),
                        HTTP_OK, TEXT_HTML);
    }

    return $response(\Tina4\renderTemplate("/google-calendar-integration/create-event.twig",
                                            ["event" => [], "linkKey" => $linkKey, "linkValue" => $linkValue,
                                             "calendars" => $calendars, "contactList" => $contactList,
                                             "promptLinkContacts" => $promptLinkContacts
                                            ]),
                    HTTP_OK, TEXT_HTML);
});

/*
 * Post route for creating an Event - creates the event
 */
Post::add("/google/calendar/create-event/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    $accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue);

    $googleCalendarIntegration = new GoogleCalendarIntegration();
    $request->params["timeZone"] = $googleCalendarIntegration->getTimeZoneOffset($request->params["timeZone"]);
    $timeStamps = $googleCalendarIntegration->convertTimeStamp(["dateTimeStart" => $request->params["dateTimeStart"],
                                                                    "dateTimeEnd" => $request->params["dateTimeEnd"]],
                                                                 $request->params["timeZone"]);

    $request->params["dateTimeStart"] = $timeStamps["dateTimeStart"];
    $request->params["dateTimeEnd"] = $timeStamps["dateTimeEnd"];

    if(!empty($request->params["attachments"])){
        foreach ($request->params["eventGuests"] as $guestEmail) {
            $attachments[] = [
                "fileId" => "",
                "fileUrl" => "",
                "iconLink" => "",
                "mimeType" => "",
                "title" => ""
            ];
        }
    }
    else
        $attachments = [];

    // If Event being created requires Hangouts or Google Meet conference
    if(isset($request->params["addMeetingToEvent"])){
        $calendarData = $googleCalendarIntegration->getCalendarData($accessToken, $request->params["eventCalendar"]);
        if(!$calendarData["error"]){
            $uniqueId = uniqid($request->params["eventCalendar"].date("Y-m-d H:i:s"));
            $createRequest = [
                "createRequest" => [
                    "conferenceSolutionKey" => [
                        "type" => $calendarData["calendarData"]["conferenceProperties"]["allowedConferenceSolutionTypes"][0]
                    ],
                    "requestId" => $uniqueId
                ]
            ];
        }
        else
            $createRequest = [
                "" => []
            ];
    }
    else
        $createRequest = [
            "" => []
        ];


    if(!empty($request->params["eventGuests"])){
        foreach ($request->params["eventGuests"] as $guestEmail) {
            $attendees[] = [
                "additionalGuests" => 0,
                "comment" => "",
                "displayName" => "",
                "email" => $guestEmail,
                "id" => "",
                "optional" => false,
                "organizer" => false,
                "resource" => false,
                "responseStatus" => "needsAction",
                "self" => false
            ];
        }
    }
    else
        $attendees = [];

    if(!empty($request->params["eventRecurrence"]))
        $recurrenceRule = [
            "0" => $request->params["eventRecurrence"]
        ];
    else
        $recurrenceRule = [];

    $eventBody = [
        "end" => [
            "dateTime" => $request->params["dateTimeEnd"],
            "timeZone" => "Africa/Johannesburg",
        ],
        "start" => [
            "dateTime" => $request->params["dateTimeStart"],
            "timeZone" => "Africa/Johannesburg",
        ],
        "attachments" => $attachments,
        "attendees" => $attendees,
        "recurrence" => $recurrenceRule,
        "conferenceData" => $createRequest,
        "summary" => $request->params["eventTitle"],
        "description" => $request->params["eventDescription"],
        "location" => $request->params["eventLocation"],
    ];


    if( (new GoogleCalendarIntegration())->saveEvent($accessToken, $request->params["eventCalendar"], $eventBody,
                                                        $linkKey, $linkValue) )
    {
        $message = "Event created.";

        return $response(renderTemplate("/google-calendar-integration/components/messages/success.twig",
                                        ["message" => $message]),
                        HTTP_OK, TEXT_HTML);
    }
    else {
        $message = "Failed to create event";

        return $response(renderTemplate("/google-calendar-integration/components/messages/failed.twig",
                                        ["message" => $message]),
                        HTTP_OK, TEXT_HTML);
    }

    return $response($message, HTTP_OK, TEXT_HTML);
});

/**
 * Get route for listing Events - displays a form to fetch events
 */
Get::add("/google/events/list/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) )
    {
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"], time() + (86400 * 30), "/");

        $link = "/google/calendar/integration/all/{$linkKey}/{$linkValue}";

        return $response(renderTemplate("/google-calendar-integration/components/auth-screen.twig", ["authLink" => $link]),
                        HTTP_OK, TEXT_HTML);
    }
    else if( !( $calendars = (new GoogleCalendarIntegration())->getCalendarList($linkKey, $linkValue, $accessToken) ) )
    {
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"], time() + (86400 * 30), "/");
        $link = "/google/calendar/integration/all/{$linkKey}/{$linkValue}";

        return $response(renderTemplate("/google-calendar-integration/components/auth-screen.twig", ["authLink" => $link]),
                        HTTP_OK, TEXT_HTML);
    }

    return $response(\Tina4\renderTemplate("google-calendar-integration/events.twig",
                                           ["calendars" => $calendars, "linkKey" => $linkKey, "linkValue" => $linkValue]),
                    HTTP_OK, TEXT_HTML);
});

/**
 * Post route for listing Events - fetches events
 */
Post::add("/google/events/list/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    $events = [];
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        $error = "Authentication failed, please reload page to continue.";
    }
    else if(!($events = (new GoogleCalendarIntegration())->listEvents($accessToken, $request->params["eventCalendar"])))
    {
        $error = "An error occurred. Please try again.";
    }
    else
        $error = null;

    return $response(renderTemplate("google-calendar-integration/components/event-list.twig",
                                            ["events" => $events, "error" => $error, "linkKey" => $linkKey,
                                             "linkValue" => $linkValue, "calendarId" => urlencode($request->params["eventCalendar"])
                                            ]),
                    HTTP_OK, TEXT_HTML);
});

/**
 * Renders a form contain Event data - used to edit an Event
 */
Get::add("/google/events/get/{calendarId}/{eventId}/{linkKey}/{linkValue}",
            function ($calendarId, $eventId, $linkKey, $linkValue, Response $response, Request $request)
{
    $event = [];
    $googleCalendarIntegration = new GoogleCalendarIntegration();
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        $error = "Authentication failed, please reload page to continue.";
        $error = true;
    }
    else if( !( $event = $googleCalendarIntegration->getEvent($calendarId, $eventId, $accessToken) ) )
    {
        $error = true;
    }
    else
        $error = false;

    if( $contactList = $googleCalendarIntegration->getContactList($linkKey, $linkValue, $accessToken) ){
        $promptLinkContacts = false;
    }
    else{
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"],time() + (86400 * 30), "/");
        $promptLinkContacts = true;
    }

    if($error){
        setcookie("authBeforeEvent", $_SERVER["REQUEST_URI"], time() + (86400 * 30), "/");

        $link = "/google/calendar/integration/all/{$linkKey}/{$linkValue}";

        return $response(renderTemplate("/google-calendar-integration/components/auth-screen.twig", ["authLink" => $link]),
                        HTTP_OK, TEXT_HTML);
    }

    return $response(renderTemplate("/google-calendar-integration/edit-event.twig", ["event" => $event,
                                                                                                 "contactList" => $contactList,
                                                                                                 "error" => $error,
                                                                                                 "calendarId" => urlencode($calendarId),
                                                                                                 "eventId" => $eventId,
                                                                                                 "linkKey" => $linkKey,
                                                                                                 "linkValue" => $linkValue,
                                                                                                 "promptLinkContacts" => $promptLinkContacts]),
                    HTTP_OK, TEXT_HTML);
});

/**
 * Will PATCH an event with data from an edit Event form
 */
Post::add("/google/calendar/edit-event/{calendarId}/{eventId}/{linkKey}/{linkValue}",
            function ($calendarId, $eventId, $linkKey, $linkValue, Response $response, Request $request)
{
    $accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue);

    if(!empty($request->params["attachments"])){
        foreach ($request->params["eventGuests"] as $guestEmail) {
            $attachments[] = [
                "fileId" => "",
                "fileUrl" => "",
                "iconLink" => "",
                "mimeType" => "",
                "title" => ""
            ];
        }
    }
    else
        $attachments = [];

    // If Event being created requires Hangouts or Google Meet conference
    if(isset($request->params["addMeetingToEvent"])){
        $createRequest = [
            "createRequest" => [
                "conferenceSolutionKey" => [
                    "type" => $request->params["conferenceSolutionKeyType"]
                ],
                "requestId" => $request->params["createRequestId"]
            ]
        ];
    }
    else
        $createRequest = [
            "" => []
        ];


    if(!empty($request->params["eventGuests"])){
        foreach ($request->params["eventGuests"] as $guestEmail) {
            $attendees[] = [
                "additionalGuests" => 0,
                "comment" => "",
                "displayName" => "",
                "email" => $guestEmail,
                "id" => "",
                "optional" => false,
                "organizer" => false,
                "resource" => false,
                "responseStatus" => "needsAction",
                "self" => false
            ];
        }
    }
    else
        $attendees = [];

    if(!empty($request->params["eventRecurrence"]))
        $recurrenceRule = [
            "0" => $request->params["eventRecurrence"]
          ];
    else
        $recurrenceRule = [];

    $eventBody = [
        "end" => [
            "dateTime" => $request->params["dateTimeEnd"],
            "timeZone" => "Africa/Johannesburg",
        ],
        "start" => [
            "dateTime" => $request->params["dateTimeStart"],
            "timeZone" => "Africa/Johannesburg",
        ],
        "attachments" => $attachments,
        "attendees" => $attendees,
        "recurrence" => $recurrenceRule,
        "conferenceData" => $createRequest,
        "summary" => $request->params["eventTitle"],
        "description" => $request->params["eventDescription"],
        "location" => $request->params["eventLocation"]
    ];


    (new GoogleCalendarIntegration())->patchEvent($accessToken, $calendarId, $eventBody, $eventId, $linkKey, $linkValue);

});


//;

/**
 * Post route for deleting an Event - deletes the event
 */
Post::add("/google/event/delete/{calendarId}/{eventId}/{linkKey}/{linkValue}",
            function ($calendarId, $eventId, $linkKey, $linkValue, Response $response, Request $request)
{
    $accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue);

    if((new GoogleCalendarIntegration())->deleteEvent($accessToken, $calendarId, $eventId, $linkKey, $linkValue))
        $message = "Event deleted.";
    else
        $message = "Failed to delete event, may have already be deleted.";

    return $response($message, HTTP_OK, TEXT_HTML);
});


Get::add("/google/calendar/test-route", function (Response $response, Request $request)
{

});