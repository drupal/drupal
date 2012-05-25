<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleInstallTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use ReflectionFunction;

/**
 * Tests for the st() function.
 */
class LocaleInstallTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'String translation using st()',
      'description' => 'Tests that st() works like t().',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp('locale');

    // st() lives in install.inc, so ensure that it is loaded for all tests.
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
  }

  /**
   * Verify that function signatures of t() and st() are equal.
   */
  function testFunctionSignatures() {
    $reflector_t = new ReflectionFunction('t');
    $reflector_st = new ReflectionFunction('st');
    $this->assertEqual($reflector_t->getParameters(), $reflector_st->getParameters(), t('Function signatures of t() and st() are equal.'));
  }
}
