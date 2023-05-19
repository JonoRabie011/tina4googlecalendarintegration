<?php

use Tina4\Api;

class GoogleCalendarAuth extends Api
{

    public $baseURL = "https://accounts.google.com/o/oauth2";

    /**
     * @var string
     */
    public string $redirectUri = "http://localhost:7145/google/calendar/get-access-token";

    private $clientId = "69673761201-25rdbvfobqoteu06f4nh3nbt3d624a4k.apps.googleusercontent.com";
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
//        parent::__construct($baseURL, $authHeader);
    }


    public function getAccessToken($code)
    {
            $body = [
                "client_id"=> $this->clientId,
                "redirect_uri" => $this->redirectUri,
                "client_secret" => $this->clientSecret,
                "code"=>$code,
                "grant_type"=>"authorization_code"
            ];

            return $this->sendRequest("/token", "POST",$body);
    }

    public function getRefreshAccessToken($accessToken)
    {
        $body = [
            "client_id"=> $this->clientId,
            "redirect_uri" => $this->redirectUri,
            "client_secret" => $this->clientSecret,
            "refresh_token" => $accessToken,
            "grant_type"=>"refresh_token"
        ];

        return $this->sendRequest("/token", "POST",$body);
    }

    public function getOAuthUri($linkDataForSql = ""): string
    {
        $authScope = urlencode('https://www.googleapis.com/auth/calendar');
        $redirectEncoded = urlencode($this->redirectUri);

        $authLink = $this->baseURL."/auth?scope={$authScope}&redirect_uri={$redirectEncoded}&response_type=code&client_id={$this->clientId}&access_type=offline";

        if(!empty($linkDataForSql))
        {
            $authLink .= "&state=".base64_encode($linkDataForSql);
        }

        return $authLink;
    }

}