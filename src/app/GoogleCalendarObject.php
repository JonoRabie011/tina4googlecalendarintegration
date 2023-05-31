<?php

class GoogleCalendarObject extends \Tina4\Data
{
    public $accessToken;
    public $linkKey;
    public $linkValue;
    public $calendarId;
    public $query;
    public $requestBody;

    public function __construct($accessToken, $linkKey, $linkValue, $calendarId, $query = [], $requestBody = [])
    {
        $this->accessToken = $accessToken;
        $this->linkKey = $linkKey;
        $this->linkValue = $linkValue;
        $this->calendarId = $calendarId;

        if(!empty($query))
        {
            $this->query = $query;
        }
        else
        {
            $this->query = [
                "conferenceDataVersion" => 1,
                "maxAttendees" => 100,
                "sendNotifications" => "true",
                "sendUpdates" => "all",
                "supportsAttachments" => "true"
            ];
        }

        if(!empty($requestBody))
        {
            $this->requestBody = $requestBody;
        }

    }
}