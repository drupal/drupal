<?php

define('MY_ORG_ID', '53deb661-3694-4336-8bd9-f4bc683ea360');
define('MY_SERVER_ID', '04337505-796a-49cb-868e-2724f7f88931');
define('MY_APP_ID', '53deb661-3694-4336-8bd9-f4bc683ea360');
define('MY_APP_INSTANCE_PROD_ID', '445de0d8-e3ac-444f-8af2-32c448160ef0');

use \Wodby\Api\Entity;

require_once __DIR__ . '/vendor/autoload.php';

$api = new Wodby\Api($_SERVER['WODBY_API_TOKEN'], new GuzzleHttp\Client());

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

// Wait until the instance will be created with timeout of 5 minutes.
$api->task()->wait($task->getId(), 600);

// Reload the instance.
$instance = $api->instance()->load($instance->getId());

var_dump($instance);
