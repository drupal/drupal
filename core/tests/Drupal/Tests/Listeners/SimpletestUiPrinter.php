<?php

/**
 * @file
 * Defines a class for providing html output links in the Simpletest UI.
 *
 * In order to manage different method signatures between PHPUnit versions, we
 * dynamically load a class dependent on the PHPUnit runner version.
 */

namespace Drupal\Tests\Listeners;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;

class_alias("Drupal\TestTools\PhpUnitCompatibility\PhpUnit" . RunnerVersion::getMajor() . "\SimpletestUiPrinter", SimpletestUiPrinter::class);
