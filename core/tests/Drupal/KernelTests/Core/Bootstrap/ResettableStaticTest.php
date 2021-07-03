<?php

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that drupal_static() and drupal_static_reset() work.
 *
 * @group Bootstrap
 * @group legacy
 */
class ResettableStaticTest extends KernelTestBase {

  /**
   * Tests drupal_static() function.
   *
   * Tests that a variable reference returned by drupal_static() gets reset when
   * drupal_static_reset() is called.
   */
  public function testDrupalStatic() {
    $name = __CLASS__ . '_' . __METHOD__;
    $this->expectDeprecation("Calling drupal_static() with '{$name}' as first argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/2661204");
    $var = &drupal_static($name, 'foo');
    $this->assertEquals('foo', $var, 'Variable returned by drupal_static() was set to its default.');

    // Call the specific reset and the global reset each twice to ensure that
    // multiple resets can be issued without odd side effects.
    $var = 'bar';
    drupal_static_reset($name);
    $this->assertEquals('foo', $var, 'Variable was reset after first invocation of name-specific reset.');
    $var = 'bar';
    $this->expectDeprecation("Calling drupal_static_reset() with '{$name}' as argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/2661204");
    drupal_static_reset($name);
    $this->assertEquals('foo', $var, 'Variable was reset after second invocation of name-specific reset.');
    $var = 'bar';
    drupal_static_reset();
    $this->assertEquals('foo', $var, 'Variable was reset after first invocation of global reset.');
    $var = 'bar';
    drupal_static_reset();
    $this->assertEquals('foo', $var, 'Variable was reset after second invocation of global reset.');

    // Check that authorized callers don't trigger deprecation errors.
    drupal_static('_batch_needs_update', 'foo');
    drupal_static_reset('_batch_needs_update');
  }

}
