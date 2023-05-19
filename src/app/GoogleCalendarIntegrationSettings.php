<?php

class GoogleCalendarIntegrationSettings
{

    /**
     * @var string
     */
    private string $integrationName = "tina4-google-calendar";


    public function saveAccessToken($accessToken,$linkKey, $linkValue)
    {

        $googleCalendarIntegration = new GoogleCalenderIntegrationORM();
        $googleCalendarIntegration->name = $this->integrationName;
        $googleCalendarIntegration->metaKey = "access_token";
        $googleCalendarIntegration->metaValue = $accessToken;
        $googleCalendarIntegration->linkKey = $linkKey;
        $googleCalendarIntegration ->linkValue = $linkValue;

        try {
            if ($googleCalendarIntegration->save())
            {
                return [
                    "error"=> false,
                    "data" => $googleCalendarIntegration
                ];
            }
        } catch (Exception $e) {
            return [
                "error"=> true,
                "data" => $e->getMessage()
            ];
        }
    }

    public function getAccessToken($linkKey, $linkValue)
    {
        $googleCalendarIntegration = new GoogleCalenderIntegrationORM();

        if($googleCalendarIntegration->load("meta_key='access_token' and {$linkKey}={$linkValue}"))
        {
            return [
                "error"=> false,
                "data" => $googleCalendarIntegration
            ];
        }
        else
        {
            return [
                "error"=> true,
                "data" => "access token not found for {$linkKey}={$linkValue}"
            ];
        }

    }


}