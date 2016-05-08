<?php

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests that drupal_static() and drupal_static_reset() work.
 *
 * @group Bootstrap
 */
class ResettableStaticUnitTest extends KernelTestBase {

  /**
   * Tests drupal_static() function.
   *
   * Tests that a variable reference returned by drupal_static() gets reset when
   * drupal_static_reset() is called.
   */
  function testDrupalStatic() {
    $name = __CLASS__ . '_' . __METHOD__;
    $var = &drupal_static($name, 'foo');
    $this->assertEqual($var, 'foo', 'Variable returned by drupal_static() was set to its default.');

    // Call the specific reset and the global reset each twice to ensure that
    // multiple resets can be issued without odd side effects.
    $var = 'bar';
    drupal_static_reset($name);
    $this->assertEqual($var, 'foo', 'Variable was reset after first invocation of name-specific reset.');
    $var = 'bar';
    drupal_static_reset($name);
    $this->assertEqual($var, 'foo', 'Variable was reset after second invocation of name-specific reset.');
    $var = 'bar';
    drupal_static_reset();
    $this->assertEqual($var, 'foo', 'Variable was reset after first invocation of global reset.');
    $var = 'bar';
    drupal_static_reset();
    $this->assertEqual($var, 'foo', 'Variable was reset after second invocation of global reset.');
  }

}
