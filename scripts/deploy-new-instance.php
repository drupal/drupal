<?php

define('MY_ORG_ID', '53deb661-3694-4336-8bd9-f4bc683ea360');
define('MY_SERVER_ID', '04337505-796a-49cb-868e-2724f7f88931');
define('MY_APP_ID', '36c5453b-ad9d-4788-ae01-dfdd74796fbc');
define('MY_APP_INSTANCE_PROD_ID', '445de0d8-e3ac-444f-8af2-32c448160ef0');

use \Wodby\Api\Entity;

require_once __DIR__ . '/vendor/autoload.php';

$api = new Wodby\Api($_SERVER['WODBY_API_TOKEN'], new GuzzleHttp\Client());

echo PHP_EOL;
echo "Creating instance.", PHP_EOL;
$result = $api->instance()->create(
  MY_APP_ID,
  'test-' . $_SERVER['BUILD_NUMBER'],
  Entity\Instance::TYPE_STAGE,
  $_SERVER['GIT_BRANCH'],
  MY_SERVER_ID,
  "[Jenkins] Test Build {$_SERVER['BUILD_DISPLAY_NAME']}",
  [
    Entity\Instance::COMPONENT_DATABASE => MY_APP_INSTANCE_PROD_ID,
    Entity\Instance::COMPONENT_FILES => MY_APP_INSTANCE_PROD_ID,
  ]
);

/** @var Entity\Task $task */
$task = $result['task'];

/** @var Entity\Instance $instance */
$instance = $result['instance'];

echo "Waiting for instance will be created", PHP_EOL;
$api->task()->wait($task->getId(), 600);

echo "Reload instance", PHP_EOL;
$instance = $api->instance()->load($instance->getId());

echo "Done!", PHP_EOL;
var_dump($instance);
