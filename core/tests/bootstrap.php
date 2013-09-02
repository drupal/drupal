<?php

// Register the namespaces we'll need to autoload from.
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Drupal\\Tests', __DIR__);

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

// Look into removing this later.
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

// Set sane locale settings, to ensure consistent string, dates, times and
// numbers handling.
// @see drupal_environment_initialize()
setlocale(LC_ALL, 'C');

// Set the default timezone. While this doesn't cause any tests to fail, PHP
// complains if 'date.timezone' is not set in php.ini.
date_default_timezone_set('UTC');
