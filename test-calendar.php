<?php

require_once "./vendor/autoload.php";
\Tina4\Initialize();
global $DBA;
$DBA = new \Tina4\DataFirebird($_ENV["DATABASE"], $_ENV["USERNAME"], $_ENV["PASSWORD"]);

$accessToken = (new GoogleCalendarAuth())->getAccessToken("clientId", "1");

$requestBody = new GoogleCalendarRequestBody([],[],"",
                                             [ ["email" => "moeketsidominicdev@gmail.com", "displayName" => "Dominic_Moe"],
                                               ["email" => "dominicmoeketsi@yahoo.com", "displayName" => "Dominic_M"]
                                             ]);

$googleCalendarObj = new GoogleCalendarObject($accessToken, "clientId", "1", "moeketsidominic@gmail.com", "",
                                              $requestBody);

if( (new GoogleCalendarEvent())->createEvent($googleCalendarObj) ){
    echo "Event Created";
}
else
    echo "Failed to create event";
