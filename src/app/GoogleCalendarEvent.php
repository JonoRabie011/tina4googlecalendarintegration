<?php

class GoogleCalendarEvent extends \Tina4\Api
{
    public $baseURL;

    public function __construct($baseURL = "https://www.googleapis.com/calendar/v3/calendars")
    {
        if(!empty($baseURL))
        {
            $this->baseURL = $baseURL;
        }
    }

    /**
     * @param $calendarId
     * @param $event
     * @param $linkKey
     * @param $linkValue
     * @param $sendNotifications
     * @return bool
     * @throws Exception
     */
    public function createEvent(GoogleCalendarObject $calendarObject) :bool
    {
        $query = "?";
        $count = 0;
        foreach ($calendarObject->query as $key => $value){
            $query .= $key . "=" . $value;
            if( ++$count != sizeof($calendarObject->query) ){
                $query .= "&";
            }
        }

        $requestBody = [];
        foreach ($calendarObject->requestBody as $key => $value){
            if(!empty($value)){
                $requestBody[$key] = $value;
            }
        }


        $this->authHeader = "Authorization: Bearer " . $calendarObject->accessToken;
        $result = $this->sendRequest("/{$calendarObject->calendarId}/events{$query}",
                                    "POST", json_encode($requestBody));


        if($result["httpCode"] == 200)
        {
            echo "<pre>";
            print_r($result);
            /* create Event record in DB*/
            if (!($eventObj = (new GoogleCalendarIntegrationORM())->load("meta_key = 'eventId'
                                                                        and meta_value = '{$result["body"]["id"]}'
                                                                        and link_key = '{$calendarObject->linkKey}'
                                                                        and link_value = '{$calendarObject->linkValue}'"))) {
                $eventObj = new GoogleCalendarIntegrationORM();

            }
            $eventObj->name = "tina4googlecalendarintegration";
            $eventObj->metaKey = "eventId";
            $eventObj->metaValue = $result["body"]["id"];
            $eventObj->linkKey = $calendarObject->linkKey;
            $eventObj->linkValue = $calendarObject->linkValue;
            $eventObj->description = "event";
            $eventObj->save();

            /* link $calenderId to relevant Event*/
            if (!($linkEventObj = (new GoogleCalendarIntegrationORM())->load("meta_key = 'calendarId'
                                                                                and meta_value = '{$calendarObject->calendarId}'
                                                                                and link_key = 'eventId'
                                                                                and link_value = '{$result["body"]["id"]}'"))) {
                $linkEventObj = new GoogleCalendarIntegrationORM();

            }
            $linkEventObj->name = "tina4googlecalendarintegration";
            $linkEventObj->metaKey = "calendarId";
            $linkEventObj->metaValue = $calendarObject->calendarId;
            $linkEventObj->linkKey = "eventId";
            $linkEventObj->linkValue = $result["body"]["id"];
            $linkEventObj->description = "calendar";
            $linkEventObj->save();

            return true;
        }
        else
            return false;
    }

}