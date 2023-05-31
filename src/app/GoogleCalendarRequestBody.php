<?php

use Carbon\Carbon;

class GoogleCalendarRequestBody
{
    public $start;
    public $end;
    public $summary;
    public $attendees;
    public $description;
    public $attachments;
    public $id;
    public $location;
    public $recurrence;
    public $reminders;
    public $status;
    public function __construct($start = [], $end = [], $summary = "", $attendees = [], $description = "",
                                $attachments = [], $id = "", $location = "", $recurrence = [], $reminders = [], $status = "")
    {
        if(!empty($dateStart))
        {
            $this->start = $start;
        }
        else{
            $this->start = ["dateTime" => date(DATE_ATOM, strtotime(Carbon::now())),
                            "timeZone" => "Africa/Johannesburg"];
        }

        if(!empty($end))
        {
            $this->end = $end;
        }
        else{
            $this->end = ["dateTime" => date(DATE_ATOM, strtotime(Carbon::now()->addHour())),
                          "timeZone" => "Africa/Johannesburg"];
        }

        if(!empty($summary))
        {
            $this->summary = $summary;
        }
        else
        {
            $this->summary = "Default event name";
        }

        if(!empty($attendees))
        {
            $this->attendees = $attendees;
        }


        if(!empty($description))
        {
            $this->description = $description;
        }

        if(!empty($attachments))
        {
            $this->attachments = $attachments;
        }

        if(!empty($id))
        {
            $this->id = $id;
        }

        if(!empty($location))
        {
            $this->location = $location;
        }

        if(!empty($recurrence))
        {
            $this->recurrence = $recurrence;
        }

        if(!empty($reminders))
        {
            $this->reminders = $reminders;
        }

        if(!empty($status))
        {
            $this->attendees = $status;
        }
    }
}