<?php

declare(strict_types=1);

namespace Drupal\Tests;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\PhpUnitWarnings
 * @group legacy
 */
class PhpUnitWarningsTest extends UnitTestCase {

  /**
   * Tests that selected PHPUnit warning is converted to deprecation.
   */
  public function testAddWarning() {
    $this->expectDeprecation('Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()');
    $this->addWarning('Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()');
  }

}
