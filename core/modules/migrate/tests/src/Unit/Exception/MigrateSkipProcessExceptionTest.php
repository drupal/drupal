<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\Exception;

use Drupal\migrate\MigrateSkipProcessException;
use Drupal\Tests\UnitTestCase;

/**
 * Tests deprecation error on MigrateSkipProcessException.
 *
 * @group legacy
 */
class MigrateSkipProcessExceptionTest extends UnitTestCase {

  /**
   * Tests a deprecation error is triggered on throw.
   */
  public function testDeprecation(): void {
    $this->expectException(MigrateSkipProcessException::class);
    $this->expectDeprecation("Unsilenced deprecation: " . MigrateSkipProcessException::class . " is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Return TRUE from a process plugin's isPipelineStopped() method to halt further processing on a pipeline. See https://www.drupal.org/node/3414511");
    throw new MigrateSkipProcessException();
  }

}
