<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that classes are correctly loaded during PHPUnit initialization.
 *
 * @group Test
 */
class PhpUnitAutoloaderTest extends UnitTestCase {

  /**
   * Tests loading of classes provided by test sub modules.
   */
  public function testPhpUnitTestClassesLoading(): void {
    $this->assertTrue(class_exists('\Drupal\phpunit_test\PhpUnitTestDummyClass'), 'Class provided by test module was not autoloaded.');
  }

}
