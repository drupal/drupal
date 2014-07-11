<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\PhpUnitAutoloaderTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that classes are correctly loaded during PHPUnit initialization.
 *
 * @group simpletest
 */
class PhpUnitAutoloaderTest extends UnitTestCase {

  /**
   * Test loading of classes provided by test sub modules.
   */
  public function testPhpUnitTestClassesLoading() {
    $this->assertTrue(class_exists('\Drupal\phpunit_test\PhpUnitTestDummyClass'), 'Class provided by test module was not autoloaded.');
  }

}
