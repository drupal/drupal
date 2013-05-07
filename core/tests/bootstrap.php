<?php

// Register the namespaces we'll need to autoload from.
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Drupal\\', __DIR__);
$loader->add('Drupal\Core', __DIR__ . "/../../core/lib");
$loader->add('Drupal\Component', __DIR__ . "/../../core/lib");

foreach (scandir(__DIR__ . "/../modules") as $module) {
  $loader->add('Drupal\\' . $module, __DIR__ . "/../modules/" . $module . "/lib");
  // Add test module classes.
  $test_modules_dir = __DIR__ . "/../modules/$module/tests/modules";
  if (is_dir($test_modules_dir)) {
    foreach (scandir($test_modules_dir) as $test_module) {
      $loader->add('Drupal\\' . $test_module, $test_modules_dir . '/' . $test_module . '/lib');
    }
  }
}

require __DIR__ . "/../../core/lib/Drupal.php";
// Look into removing this later.
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

// Set sane locale settings, to ensure consistent string, dates, times and
// numbers handling.
// @see drupal_environment_initialize()
setlocale(LC_ALL, 'C');
