<?php

use Tina4\Get;
use Tina4\Post;
use Tina4\Response;
use Tina4\Request;
use function Tina4\redirect;
use function Tina4\renderTemplate;

Get::add("/google/landing", function (Response $response) {

    return $response(\Tina4\renderTemplate("google-calendar-integration/landing.twig"),
                    HTTP_OK, TEXT_HTML);
});


Get::add("/google/calendar/integration/{authScope}/{linkKey}/{linkValue}", function ($authScope, $linkKey, $linkValue, Response $response) {

    $googleAuth = new GoogleCalendarAuth();

    $clientInfo = [
        $linkKey => $linkValue,
        "userId" => 55,
        "adminId" => 5
    ];

    switch($authScope){
        case "calendar":
            $authUrl = "https://www.googleapis.com/auth/calendar";
            break;
        case "contacts":
            $authUrl = "https://www.googleapis.com/auth/contacts.other.readonly";
            break;
        default:
            $authUrl = "https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/contacts.other.readonly";
            break;
    }

    $authUri = $googleAuth->getOAuthUri(serialize($clientInfo), $authUrl);

    \Tina4\redirect($authUri);
//    return $response(\Tina4\renderTemplate("google-calendar-integration/google-calendar-integration-oauth.twig", [
//        "authUri" => $authUri
//        ])
//    , HTTP_OK, TEXT_HTML);
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

    if(isset( $_COOKIE["authBeforeCreateEvent"] ))
    {
        setcookie("authBeforeCreateEvent","",time() - 3600, "/");
        redirect($_COOKIE["authBeforeCreateEvent"]);
    }

    return $response("Access");
});

Get::add("/google/calendar/create-event/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        setcookie("authBeforeCreateEvent", "/google/calendar/create-event/{linkKey}/{linkValue}", time() + (86400 * 30), "/");
        \Tina4\redirect("/google/calendar/integration/all/{$linkKey}/{$linkValue}");
    }

    if( $contactList = (new GoogleCalendarIntegration())->getContactList($linkKey, $linkValue, $accessToken) ){
        $promptLinkContacts = false;
    }
    else{
        setcookie("authBeforeCreateEvent","/google/calendar/create-event/{$linkKey}/{$linkValue}",time() + (86400 * 30), "/");
        $promptLinkContacts = true;
    }

    if( !( $calendars = (new GoogleCalendarIntegration())->getCalendarList($linkKey, $linkValue, $accessToken) ) ) {
        setcookie("authBeforeCreateEvent", "/google/calendar/create-event/{$linkKey}/{$linkValue}", time() + (86400 * 30), "/");
        \Tina4\redirect("/google/calendar/integration/all/{$linkKey}/{$linkValue}");
    }

    return $response(\Tina4\renderTemplate("google-calendar-integration/create-event.twig",
                                            ["event" => [], "linkKey" => $linkKey, "linkValue" => $linkValue,
                                             "calendars" => $calendars, "contactList" => $contactList,
                                             "promptLinkContacts" => $promptLinkContacts
                                            ]),
                    HTTP_OK, TEXT_HTML);
});

Post::add("/google/calendar/create-event/{linkKey}/{linkValue}",
          function ($linkKey, $linkValue, Response $response, Request $request)
{
    $googleCalendarIntegration = new GoogleCalendarIntegration();
    $request->params["timeZone"] = $googleCalendarIntegration->getTimeZoneOffset($request->params["timeZone"]);
    $timeStamps = $googleCalendarIntegration->convertTimeStamp(["dateTimeStart" => $request->params["dateTimeStart"],
                                                                    "dateTimeEnd" => $request->params["dateTimeEnd"]],
                                                                 $request->params["timeZone"]);

    $request->params["dateTimeStart"] = $timeStamps["dateTimeStart"];
    $request->params["dateTimeEnd"] = $timeStamps["dateTimeEnd"];

    if(!empty($request->params["eventGuests"])){
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
        $recurrenceRule = $request->params["eventRecurrence"];
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
        "recurrence" => [
            $recurrenceRule,
        ],
        "summary" => $request->params["eventTitle"],
        "description" => $request->params["eventDescription"],
        "location" => $request->params["eventLocation"],
    ];

    $accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue);

    if( (new GoogleCalendarIntegration())->saveEvent($accessToken, $request->params["eventCalendar"], $eventBody,
                                                        $linkKey, $linkValue) )
    {
        $message = "Event created.";
    }
    else {
        $message = "Failed to create event";
    }

    return $response($message, HTTP_OK, TEXT_HTML);
});

Get::add("/google/events/list/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        setcookie("authBeforeCreateEvent", "/google/events/list/{$linkKey}/{$linkValue}", time() + (86400 * 30), "/");
        \Tina4\redirect("/google/calendar/integration/all/{$linkKey}/{$linkValue}");
    }
    else if( !( $calendars = (new GoogleCalendarIntegration())->getCalendarList($linkKey, $linkValue, $accessToken) ) ) {
        setcookie("authBeforeCreateEvent", "/google/events/list/{$linkKey}/{$linkValue}", time() + (86400 * 30), "/");
        \Tina4\redirect("/google/calendar/integration/all/{$linkKey}/{$linkValue}");
    }

    return $response(\Tina4\renderTemplate("google-calendar-integration/events.twig",
                                           ["calendars" => $calendars, "linkKey" => $linkKey, "linkValue" => $linkValue,
                                           ]),
                    HTTP_OK, TEXT_HTML);
});

Post::add("/google/events/list/{linkKey}/{linkValue}", function ($linkKey, $linkValue, Response $response, Request $request)
{
    $events = [];
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        $error = "Authentication failed, please reload page to continue.";
    }
    else if( !( $events = (new GoogleCalendarIntegration())
                          ->saveEvent($accessToken, $request->params["eventCalendar"], null, null,
                                     null, "") ) )
    {
        $error = "An error occurred. Please try again.";
    }
    else
        $error = null;

    return $response(renderTemplate("google-calendar-integration/components/event-list.twig",
                                            ["events" => $events, "error" => $error, "linkKey" => $linkKey,
                                             "linkValue" => $linkValue, "calendarId" => $request->params["eventCalendar"]
                                            ]),
                    HTTP_OK, TEXT_HTML);
});

Post::add("/google/events/get/{calendarId}/{eventId}/{linkKey}/{linkValue}",
            function ($calendarId, $eventId, $linkKey, $linkValue, Response $response, Request $request)
{
    $event = [];
    if( ! ($accessToken = (new GoogleCalendarAuth())->getAccessToken($linkKey, $linkValue)) ) {
        $error = "Authentication failed, please reload page to continue.";
    }
    else if( !( $event = (new GoogleCalendarIntegration())->getCalendarEvent($request->params["calendarId"],
                                                                             $request->params["eventId"], $linkKey,
                                                                             $linkValue, $accessToken) ) )
    {
        $error = "Authentication failed, please reload page to continue.";
    }
    else
        $error = null;

    return $response(["event" => $event, "error" => $error, "linkKey" => $linkKey, "linkValue" => $linkValue,
                      "calendarId" => $request->params["calendarId"], "eventId" => $request->params["eventId"]],
                    HTTP_OK, TEXT_HTML);
});

Get::add("/google/calendar-i-frame", function (Response $response, Request $request)
{
    echo "<pre>";
    print_r((new GoogleCalendarIntegrationORM())->load("name = 'tina4googlecalendarintegration' 
                                                                and link_key = 'clientId' 
                                                                and link_value = '1'
                                                                and meta_key = 'refresh_token'"));
    exit;
});