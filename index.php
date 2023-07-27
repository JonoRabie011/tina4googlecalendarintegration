<?php
require_once "./vendor/autoload.php";

global $DBA;
$DBA = new \Tina4\DataFirebird($_ENV["DATABASE"], $_ENV["USERNAME"], $_ENV["PASSWORD"]);

$config = new \Tina4\Config();
\Tina4\Initialize();
echo new \Tina4\Tina4Php($config);