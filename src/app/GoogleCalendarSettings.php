<?php

class GoogleCalendarSettings
{
    public function getAuthScopeDescription($scope): string
    {
        if( str_contains($scope,"calendar") && str_contains($scope, "contacts") )
        {
            $description = "all";
        }
        else if( str_contains($scope,"calendar") )
        {
            $description = "calendar";
        }
        else if( str_contains($scope,"contacts") )
        {
            $description = "contact";
        }
        else{
            $description = "code";
        }

        return $description;
    }

    public function saveSettingsInformation($linkKey, $linkValue, $metaKey, $metaValue, $description): void
    {
        if( !((new GoogleCalendarIntegrationORM())
                    ->load("name = 'tina4googlecalendarintegration' 
                            and link_key = '{$linkKey}' and link_value = '{$linkValue}'
                            and meta_key = '{$metaKey}'"))
        )
        {
            $integrationORMObject = new GoogleCalendarIntegrationORM();
            $integrationORMObject->name = "tina4googlecalendarintegration";
            $integrationORMObject->linkKey = $linkKey;
            $integrationORMObject->linkValue = $linkValue;
            $integrationORMObject->metaKey = $metaKey;
            $integrationORMObject->description = $description;
            $integrationORMObject->metaValue = $metaValue;
            $integrationORMObject->save();
        }
    }

    public function getSettingsInformation($linkKey, $linkValue, $metaKey){
        return (new GoogleCalendarIntegrationORM())->load("name = 'tina4googlecalendarintegration' 
                                                                and link_key = '{$linkKey}' 
                                                                and link_value = '{$linkValue}'
                                                                and meta_key = '{$metaKey}'")->metaValue;
    }
}