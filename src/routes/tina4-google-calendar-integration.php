<?php

use Tina4\Get;
use Tina4\Response;
use Tina4\Request;

Get::add("/google/calendar/integration", function (Response $response) {

    $googleAuth = new GoogleCalendarAuth();

    $clientInfo = [
        "clientId" => 1
    ];

    $authUri = $googleAuth->getOAuthUri(serialize($clientInfo));

    return $response(\Tina4\renderTemplate("google-calendar-integration/google-calendar-integration-oauth.twig", [
        "authUri" => $authUri
        ])
    , HTTP_OK, TEXT_HTML);
});

Get::add("/google/calendar/get-access-token", function (Response $response, Request $request) {

    print_r(unserialize(base64_decode($request->params["state"])));

    return $response("Access");
});