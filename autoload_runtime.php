<?php

/**
 * @file
 * Includes the autoload_runtime created by the Symfony Runtime component.
 *
 * @see composer.json
 * @see index.php
 * @see core/install.php
 * @see core/rebuild.php
 */

use Drupal\Core\Runtime\DrupalRuntime;

// By default, the symfony/runtime component would load SymfonyRuntime as its
// runtime. However, Drupal's Kernel has a lot of runtime components that it
// expects to be prepared. Thus, we default Drupal applications to DrupalRuntime
// instead to make this easily accessible.
$_ENV['APP_RUNTIME'] ??= $_SERVER['APP_RUNTIME'] ?? DrupalRuntime::class;
return require __DIR__ . '/vendor/autoload_runtime.php';
