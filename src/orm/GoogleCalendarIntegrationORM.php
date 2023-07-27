<?php

class GoogleCalendarIntegrationORM extends \Tina4\ORM
{
    public $tableName = "tina4_integration";
    public $primaryKey = "id";
    public $genPrimaryKey = true;
    
    public $id;
    public $name;
    public $description;
    public $metaKey;
    public $metaValue;
    public $linkKey;
    public $linkValue;
    
}