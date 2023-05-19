<?php

use Tina4\ORM;
class GoogleCalenderIntegrationORM extends ORM
{
    public $tableName = "tina4_integration";
    public $primaryKey = "id";
    public $genPrimaryKey = true;

    public $id;
    public $name;
    public $metaKey;
    public $metaValue;
    public $linkKey;
    public $linkValue;
}