<?php

define('MY_ORG_ID', '53deb661-3694-4336-8bd9-f4bc683ea360');

require_once __DIR__ . './vendor/autoload.php';

$api = new Wodby\Api($_SERVER['WODBY_API_TOKEN'], new GuzzleHttp\Client());

$apps = $api->application()->loadAll('MY_ORG_ID');

var_dump($apps);
