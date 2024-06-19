<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use PHPUnit\Framework\TestCase;

/**
 * @group Test
 */
class TestSuiteBaseTest extends TestCase {

  /**
   * Tests the assumption that local time is in 'Australia/Sydney'.
   */
  public function testLocalTimeZone(): void {
    // The 'Australia/Sydney' time zone is set in core/tests/bootstrap.php
    $this->assertEquals('Australia/Sydney', date_default_timezone_get());
  }

}
