<?php

namespace Drupal\KernelTests;

/**
 * Translates Simpletest assertion methods to PHPUnit.
 *
 * Protected methods are custom. Public static methods override methods of
 * \PHPUnit\Framework\Assert.
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
 *   PHPUnit's native assert methods instead.
 *
 * @see https://www.drupal.org/node/3129738
 */
trait AssertLegacyTrait {

  /**
   * @see \Drupal\simpletest\TestBase::assert()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
   *   $this->assertTrue() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assert($actual, $message = '') {
    @trigger_error('AssertLegacyTrait::assert() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertTrue() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    parent::assertTrue((bool) $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertEqual()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
   *   $this->assertEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertEqual($actual, $expected, $message = '') {
    @trigger_error('AssertLegacyTrait::assertEqual() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertEquals($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotEqual()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
   *   $this->assertNotEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNotEqual($actual, $expected, $message = '') {
    @trigger_error('AssertLegacyTrait::assertNotEqual() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertNotEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertNotEquals($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdentical()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSame() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertIdentical($actual, $expected, $message = '') {
    @trigger_error('AssertLegacyTrait::assertIdentical() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertSame() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSame($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotIdentical()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
   *   $this->assertNotSame() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNotIdentical($actual, $expected, $message = '') {
    @trigger_error('AssertLegacyTrait::assertNotIdentical() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertNotSame() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertNotSame($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdenticalObject()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use
   *   $this->assertEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertIdenticalObject($actual, $expected, $message = '') {
    @trigger_error('AssertLegacyTrait::assertIdenticalObject() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    // Note: ::assertSame checks whether its the same object. ::assertEquals
    // though compares

    $this->assertEquals($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::pass()
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. PHPUnit
   *   interrupts a test as soon as a test assertion fails, so there is usually
   *   no need to call this method. If a test's logic relies on this method,
   *   refactor the test.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function pass($message) {
    @trigger_error('AssertLegacyTrait::pass() is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. PHPUnit interrupts a test as soon as a test assertion fails, so there is usually no need to call this method. If a test\'s logic relies on this method, refactor the test. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertTrue(TRUE, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::verbose()
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use
   *   dump() instead.
   *
   * @see https://www.drupal.org/node/3197514
   */
  protected function verbose($message) {
    @trigger_error('AssertLegacyTrait::verbose() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use dump() instead. See https://www.drupal.org/node/3197514', E_USER_DEPRECATED);
    if (in_array('--debug', $_SERVER['argv'], TRUE)) {
      // Write directly to STDOUT to not produce unexpected test output.
      // The STDOUT stream does not obey output buffering.
      fwrite(STDOUT, $message . "\n");
    }
  }

}
