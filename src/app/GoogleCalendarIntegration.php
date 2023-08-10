<?php

class GoogleCalendarIntegration extends \Tina4\Api
{

    public $baseURL = "https://www.googleapis.com/calendar/v3/calendars";
    public string $redirectUri = "http://localhost:7145/google/calendar/get-access-token";

    private $clientId = "851127021703-q87n1fjql7dneqo5giq823uqk7kka004.apps.googleusercontent.com";
    private $clientSecret = "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";

    public function __construct($baseURL = "", $clientId = "", $clientSecret = "")
    {
        if(!empty($baseURL))
        {
            $this->baseURL = $baseURL;
        }

        if(!empty($clientId))
        {
            $this->clientId = $clientId;
        }

        if(!empty($clientSecret))
        {
            $this->clientSecret = $clientSecret;
        }
    }

    /**
     * Creates an Event in specified Calendar.
     *
     * @param $accessToken
     * @param $calendarId
     * @param $eventBody
     * @param $linkKey
     * @param $linkValue
     * @param $restServiceSuffix
     * @return mixed
     * @throws Exception
     */
    public function saveEvent($accessToken, $calendarId, $eventBody, $linkKey, $linkValue): mixed
    {
        $this->authHeader = "Authorization: Bearer " . $accessToken;

        $result = $this->sendRequest("/{$calendarId}/events?conferenceDataVersion=1&sendNotifications=true",
                                       "POST",json_encode($eventBody));

        if($result["httpCode"] == 200)
        {
            /* save Event record in our DB */
            if (! ((new GoogleCalendarIntegrationORM())->load("meta_key = 'eventId'
                                                                    and meta_value = '{$result["body"]["id"]}'
                                                                    and link_key = '{$linkKey}'
                                                                    and link_value = '{$linkValue}'")) )
            {
                $eventObj = new GoogleCalendarIntegrationORM();
                $eventObj->name = "tina4googlecalendarintegration";
                $eventObj->metaKey = "eventId";
                $eventObj->metaValue = $result["body"]["id"];
                $eventObj->linkKey = $linkKey;
                $eventObj->linkValue = $linkValue;
                $eventObj->description = "event";
                $eventObj->save();
            }

            /* create a record for this $calendarId if nonexistent */
            if (! ((new GoogleCalendarIntegrationORM())->load("meta_key = 'calendarId'
                                                                    and meta_value = '{$calendarId}'
                                                                    and link_key = '{$linkKey}'
                                                                    and link_value = '{$linkValue}'")) )
            {
                $linkEventObj = new GoogleCalendarIntegrationORM();
                $linkEventObj->name = "tina4googlecalendarintegration";
                $linkEventObj->metaKey = "calendarId";
                $linkEventObj->metaValue = $calendarId;
                $linkEventObj->linkKey = $linkKey;
                $linkEventObj->linkValue = $linkValue;
                $linkEventObj->description = "calendar";
                $linkEventObj->save();
            }
            return true;
        }
        else
            return false;
    }


    /**
     * Patches specified Event in specified Calendar.
     *
     * @param $accessToken
     * @param $calendarId
     * @param $eventBody
     * @param $eventId
     * @param $linkKey
     * @param $linkValue
     * @return bool
     * @throws Exception
     */
    public function patchEvent($accessToken, $calendarId, $eventBody, $eventId, $linkKey, $linkValue) : bool
    {
        $this->authHeader = "Authorization: Bearer " . $accessToken;

        $result = $this->sendRequest("/{$calendarId}/events/{$eventId}?conferenceDataVersion=1&sendUpdates=all",
                                                           "PUT",json_encode($eventBody));


        // if PATCH was successful
        if($result["httpCode"] == 200)
        {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Deletes specified Event in specified Calendar.
     *
     * @param $accessToken
     * @param $calendarId
     * @param $eventId
     * @param $linkKey
     * @param $linkValue
     * @return bool
     * @throws Exception
     */
    public function deleteEvent($accessToken, $calendarId, $eventId, $linkKey, $linkValue) : bool
    {
        $this->authHeader = "Authorization: Bearer " . $accessToken;

        $result = $this->sendRequest("/{$calendarId}/events/{$eventId}?sendNotifications=true", "DELETE");

        if($result["httpCode"] == 204)      // Google Calendar API DELETE returns HTTP 204 on success
        {
            $googleCalendarIntegration = new GoogleCalendarIntegrationORM();

            /* delete Event record in our DB */
            if (($googleCalendarIntegration->load("meta_key = 'eventId'
                                                    and meta_value = '{$eventId}'
                                                    and link_key = '{$linkKey}'
                                                    and link_value = '{$linkValue}'")))
            {
                $googleCalendarIntegration->delete("meta_key = 'eventId'
                                                    and meta_value = '{$eventId}'
                                                    and link_key = '{$linkKey}'
                                                    and link_value = '{$linkValue}'");
            }

            return true;
        }
        else
            return false;
    }

    /**
     * Gets data on specified Event.
     *
     * @param $calendarId
     * @param $eventId
     * @param $linkKey
     * @param $linkValue
     * @param $accessToken
     * @return mixed
     */
    public function getEvent($calendarId, $eventId, $accessToken) : mixed
    {
        $this->authHeader = "Authorization: Bearer " . $accessToken;
        $result =  $this->sendRequest("/{$calendarId}/events/{$eventId}");


        if($result["httpCode"] == 200)
        {
            return $result["body"];
        }
        else {
            return false;
        }
    }

    public function getTimeZoneOffset($timeZone)
    {
        $this->baseURL = "http://worldtimeapi.org/api/timezone";

        return $this->sendRequest("/{$timeZone}")["body"]["utc_offset"];
    }

    public function convertTimeStamp(array $timeStamp, string $timeZone) : array
    {
        foreach ($timeStamp as $key => $value){
            $value = date('Y-m-d H:i:s', strtotime($timeStamp[$key]));
            $timeStamp[$key] = str_replace(" ", "T", $value) . $timeZone;
        }

        return $timeStamp;
    }

    /**
     * Gets a list of Calendars for $linkKey.
     *
     * @param $linkKey
     * @param $linkValue
     * @param $accessToken
     * @return array
     */
    public function getCalendarList($linkKey, $linkValue, $accessToken) : array
    {
        if( (new GoogleCalendarIntegrationORM())->load("description = 'all' or description = 'calendar' 
                                                        and meta_key = 'access_token' and meta_value = '{$accessToken}'
                                                        and link_key = '{$linkKey}' and link_value = '{$linkValue}'") )
        {
            $this->baseURL = "https://www.googleapis.com/calendar/v3";
            $this->authHeader = "Authorization: Bearer " . $accessToken;
            $calendars = $this->sendRequest("/users/me/calendarList");

            if($calendars["httpCode"] == 200){
                return array_reverse($calendars["body"]["items"]);
            }
            else{
                return [];
            }
        }
        else {
            return [];
        }
    }

    /**
     * Returns data for given calendar using $calendarId.
     *
     * @param $accessToken
     * @param $calendarId
     * @return array|true[]
     */
    public function getCalendarData($accessToken, $calendarId) : array
    {
        $this->baseURL = "https://www.googleapis.com/calendar/v3";
        $this->authHeader = "Authorization: Bearer " . $accessToken;
        $calendarData = $this->sendRequest("/users/me/calendarList/{$calendarId}");

        if($calendarData["httpCode"] == 200){
            return ["error" => false, "calendarData" => $calendarData["body"]];
        }
        else{
            return ["error" => true];
        }
    }

    /**
     * Gets a list of Events in $calendarId .
     *
     * @param $accessToken
     * @param $calendarId
     * @return mixed
     */
    public function listEvents($accessToken, $calendarId) : mixed
    {
        $this->authHeader = "Authorization: Bearer " . $accessToken;
        $result =  $this->sendRequest("/{$calendarId}/events");

        if($result["httpCode"] == 200)
        {
            return $result["body"]["items"];
        }
        else {
            return false;
        }
    }

    /**
     * Get list of Contacts associated to $linkKey .
     *
     * @param $linkKey
     * @param $linkValue
     * @param $accessToken
     * @return array
     */
    public function getContactList($linkKey, $linkValue, $accessToken) : array
    {
        $contactList = [];

        if( (new GoogleCalendarIntegrationORM())->load("description = 'all' or description = 'contact' 
                                                        and meta_key = 'access_token' and meta_value = '{$accessToken}'
                                                        and link_key = '{$linkKey}' and link_value = '{$linkValue}'") )
        {
            $this->baseURL = "https://people.googleapis.com";
            $this->authHeader = "Authorization: Bearer " . $accessToken;
            $contacts = $this->sendRequest("/v1/otherContacts?readMask=names,emailAddresses");

            if($contacts["httpCode"] == 200){
                foreach ($contacts["body"]["otherContacts"] as $contact)
                {
                    foreach ($contact["emailAddresses"] as $contactNode){
                        $contactList[] = $contactNode["value"];
                    }
                }
            }
        }

        return $contactList;
    }
}