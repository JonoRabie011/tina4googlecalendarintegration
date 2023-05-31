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
     * @param $accessToken
     * @param $calendarId
     * @param $eventBody
     * @param $linkKey
     * @param $linkValue
     * @param $restServiceSuffix
     * @return bool|mixed
     * @throws Exception
     */
    public function saveEvent($accessToken, $calendarId, $eventBody, $linkKey, $linkValue, $restServiceSuffix = ""){

        $this->authHeader = "Authorization: Bearer " . $accessToken;

        if( $eventBody != null ) {
            $sendNotifications = "?sendNotifications=true";
            $result = $this->sendRequest("/{$calendarId}/events{$restServiceSuffix}{$sendNotifications}",
                                                               "POST",json_encode($eventBody));
        }
        else {
            $sendNotifications = "";
            $result = $this->sendRequest("/{$calendarId}/events{$restServiceSuffix}{$sendNotifications}",
                                         "GET");
        }

        return $result;
        if($result["httpCode"] == 200)
        {
            if( $eventBody != null )
            {
                /* save Event record in our DB */
                if (!($eventObj = (new GoogleCalendarIntegrationORM())->load("meta_key = 'eventId'
                                                                            and meta_value = '{$result["body"]["id"]}'
                                                                            and link_key = '{$linkKey}'
                                                                            and link_value = '{$linkValue}'"))) {
                    $eventObj = new GoogleCalendarIntegrationORM();

                }
                $eventObj->name = "tina4googlecalendarintegration";
                $eventObj->metaKey = "eventId";
                $eventObj->metaValue = $result["body"]["id"];
                $eventObj->linkKey = $linkKey;
                $eventObj->linkValue = $linkValue;
                $eventObj->description = "event";
                $eventObj->save();

                /* link $calenderId to relevant Event */
                if (!($linkEventObj = (new GoogleCalendarIntegrationORM())->load("meta_key = 'calendarId'
                                                                                    and meta_value = '{$calendarId}'
                                                                                    and link_key = 'eventId'
                                                                                    and link_value = '{$result["body"]["id"]}'"))) {
                    $linkEventObj = new GoogleCalendarIntegrationORM();

                }
                $linkEventObj->name = "tina4googlecalendarintegration";
                $linkEventObj->metaKey = "calendarId";
                $linkEventObj->metaValue = $calendarId;
                $linkEventObj->linkKey = "eventId";
                $linkEventObj->linkValue = $result["body"]["id"];
                $linkEventObj->description = "calendar";
                $linkEventObj->save();

                return true;
            }
            else
            {
                return $result["body"]["items"];
            }
        }
        else
            return false;
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

            return array_reverse($this->sendRequest("/users/me/calendarList")["body"]["items"]);
        }
        else {
            return [];
        }
    }

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

    public function getCalendarEvent($calendarId, $eventId, $linkKey, $linkValue, $accessToken) : mixed
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

    public function getContactList($linkKey, $linkValue, $accessToken) :array
    {
        $contactList = [];

        if( (new GoogleCalendarIntegrationORM())->load("description = 'all' or description = 'contact' 
                                                        and meta_key = 'access_token' and meta_value = '{$accessToken}'
                                                        and link_key = '{$linkKey}' and link_value = '{$linkValue}'") )
        {
            $this->baseURL = "https://people.googleapis.com";
            $this->authHeader = "Authorization: Bearer " . $accessToken;
            $contacts = $this->sendRequest("/v1/otherContacts?readMask=names,emailAddresses")["body"]["otherContacts"];

            foreach ($contacts as $contact)
            {
                foreach ($contact["emailAddresses"] as $contactNode){
                    $contactList[] = $contactNode["value"];
                }
            }

        }

        return $contactList;
    }
}