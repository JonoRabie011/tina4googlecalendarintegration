<?php

use Tina4\Api;

class GoogleCalendarAuth extends Api
{

    public $baseURL = "https://accounts.google.com/o/oauth2";

    /**
     * @var string
     */
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


    public function createAccessToken($code)
    {
        $body = [
            "client_id"=> $this->clientId,
            "redirect_uri" => $this->redirectUri,
            "client_secret" => $this->clientSecret,
            "code"=>$code,
            "grant_type"=>"authorization_code"
        ];

        return $this->sendRequest("/token", "POST",json_encode($body));
    }

    public function refreshAccessToken($refreshToken)
    {
        $body = [
            "client_id"=> $this->clientId,
            "redirect_uri" => $this->redirectUri,
            "client_secret" => $this->clientSecret,
            "refresh_token" => $refreshToken,
            "grant_type"=>"refresh_token"
        ];

        return $this->sendRequest("/token", "POST",json_encode($body));
    }
    public function getAccessToken($linkKey, $linkValue)
    {
        $googleCalendarSettings = new GoogleCalendarSettings();
        $refreshToken = $googleCalendarSettings->getSettingsInformation($linkKey, $linkValue, "refresh_token");


        $requestRefreshAccessToken = $this->refreshAccessToken($refreshToken);

        if( !($requestRefreshAccessToken["httpCode"] == 200) ){
            return false;
        }
        else{
            $accessTokenBody = $requestRefreshAccessToken["body"];
            $accessToken = $accessTokenBody["access_token"];
            $description  = $googleCalendarSettings->getAuthScopeDescription($accessTokenBody["scope"]);

            $googleCalendarSettings->saveSettingsInformation($linkKey, $linkValue, "access_token", $accessToken, $description);

            return $accessToken;
        }
    }

    public function getOAuthUri($linkDataForSql = "", $authUrl = ""): string
    {
        $authScope = urlencode($authUrl);
        $redirectEncoded = urlencode($this->redirectUri);

        $authLink = $this->baseURL."/auth?scope={$authScope}&redirect_uri={$redirectEncoded}&response_type=code&client_id={$this->clientId}&access_type=offline";

        if(!empty($linkDataForSql))
        {
            $authLink .= "&state=".base64_encode($linkDataForSql);
        }

        return $authLink;
    }
}