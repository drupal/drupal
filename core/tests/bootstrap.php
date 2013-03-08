<?php

// Register the namespaces we'll need to autoload from.
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Drupal\\', __DIR__);
$loader->add('Drupal\Core', __DIR__ . "/../../core/lib");
$loader->add('Drupal\Component', __DIR__ . "/../../core/lib");

foreach (scandir(__DIR__ . "/../modules") as $module) {
  $loader->add('Drupal\\' . $module, __DIR__ . "/../modules/" . $module . "/lib");
}
// Look into removing this later.
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);
