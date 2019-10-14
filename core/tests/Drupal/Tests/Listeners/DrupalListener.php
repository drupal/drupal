<?php

/**
 * @file
 * Listens to PHPUnit test runs.
 *
 * In order to manage different method signatures between PHPUnit versions, we
 * dynamically load a class dependent on the PHPUnit runner version.
 */

namespace Drupal\Tests\Listeners;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;

class_alias("Drupal\TestTools\PhpUnitCompatibility\PhpUnit" . RunnerVersion::getMajor() . "\DrupalListener", DrupalListener::class);
