<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that drupal_static() and drupal_static_reset() work.
 *
 * @group Bootstrap
 */
class ResettableStaticTest extends KernelTestBase {

  /**
   * Tests drupal_static() function.
   *
   * Tests that a variable reference returned by drupal_static() gets reset when
   * drupal_static_reset() is called.
   */
  public function testDrupalStatic(): void {
    $name = __CLASS__ . '_' . __METHOD__;
    $var = &drupal_static($name, 'foo');
    $this->assertEquals('foo', $var, 'Variable returned by drupal_static() was set to its default.');

    // Call the specific reset and the global reset each twice to ensure that
    // multiple resets can be issued without odd side effects.
    $var = 'bar';
    drupal_static_reset($name);
    $this->assertEquals('foo', $var, 'Variable was reset after first invocation of name-specific reset.');
    $var = 'bar';
    drupal_static_reset($name);
    $this->assertEquals('foo', $var, 'Variable was reset after second invocation of name-specific reset.');
    $var = 'bar';
    drupal_static_reset();
    $this->assertEquals('foo', $var, 'Variable was reset after first invocation of global reset.');
    $var = 'bar';
    drupal_static_reset();
    $this->assertEquals('foo', $var, 'Variable was reset after second invocation of global reset.');

    // Test calling drupal_static() with no arguments (empty string).
    $name1 = __CLASS__ . '_' . __METHOD__ . '1';
    $name2 = '';
    $var1 = &drupal_static($name1, 'initial1');
    $var2 = &drupal_static($name2, 'initial2');
    $this->assertEquals('initial1', $var1, 'Variable 1 returned by drupal_static() was set to its default.');
    $this->assertEquals('initial2', $var2, 'Variable 2 returned by drupal_static() was set to its default.');
    $var1 = 'modified1';
    $var2 = 'modified2';
    drupal_static_reset($name1);
    drupal_static_reset($name2);
    $this->assertEquals('initial1', $var1, 'Variable 1 was reset after invocation of name-specific reset.');
    $this->assertEquals('initial2', $var2, 'Variable 2 was reset after invocation of name-specific reset.');
    $var1 = 'modified1';
    $var2 = 'modified2';
    drupal_static_reset();
    $this->assertEquals('initial1', $var1, 'Variable 1 was reset after invocation of global reset.');
    $this->assertEquals('initial2', $var2, 'Variable 2 was reset after invocation of global reset.');
  }

}
