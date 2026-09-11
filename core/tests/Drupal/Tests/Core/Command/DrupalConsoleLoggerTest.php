<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Command;

use Drupal\Core\Command\DrupalConsoleLogger;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\InvalidArgumentException;

/**
 * Tests DrupalConsoleLogger.
 */
#[Group('command')]
#[CoversClass(DrupalConsoleLogger::class)]
class DrupalConsoleLoggerTest extends UnitTestCase {

  /**
   * Tests the exception thrown by ::toPsr3() due to an invalid argument.
   */
  public function testToPsr3Exception(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageIs('Invalid log level: -1000');
    DrupalConsoleLogger::toPsr3(-1000);
  }

}
