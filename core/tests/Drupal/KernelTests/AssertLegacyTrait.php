<?php

namespace Drupal\KernelTests;

/**
 * Translates Simpletest assertion methods to PHPUnit.
 *
 * Protected methods are custom. Public static methods override methods of
 * \PHPUnit\Framework\Assert.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0. Use PHPUnit's native
 *   assert methods instead.
 */
trait AssertLegacyTrait {

  /**
   * @see \Drupal\simpletest\TestBase::assert()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use self::assertTrue()
   *   instead.
   */
  protected function assert($actual, $message = '') {
    parent::assertTrue((bool) $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertTrue()
   */
  public static function assertTrue($actual, $message = '') {
    if (is_bool($actual)) {
      parent::assertTrue($actual, $message);
    }
    else {
      parent::assertNotEmpty($actual, $message);
    }
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertFalse()
   */
  public static function assertFalse($actual, $message = '') {
    if (is_bool($actual)) {
      parent::assertFalse($actual, $message);
    }
    else {
      parent::assertEmpty($actual, $message);
    }
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertEqual()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use self::assertEquals()
   *   instead.
   */
  protected function assertEqual($actual, $expected, $message = '') {
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotEqual()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use
   *   self::assertNotEquals() instead.
   */
  protected function assertNotEqual($actual, $expected, $message = '') {
    $this->assertNotEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdentical()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use self::assertSame()
   *   instead.
   */
  protected function assertIdentical($actual, $expected, $message = '') {
    $this->assertSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotIdentical()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use
   *   self::assertNotSame() instead.
   */
  protected function assertNotIdentical($actual, $expected, $message = '') {
    $this->assertNotSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdenticalObject()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use self::assertEquals()
   *   instead.
   */
  protected function assertIdenticalObject($actual, $expected, $message = '') {
    // Note: ::assertSame checks whether its the same object. ::assertEquals
    // though compares

    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::pass()
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0. Use self::assertTrue()
   *   instead.
   */
  protected function pass($message) {
    $this->assertTrue(TRUE, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::verbose()
   */
  protected function verbose($message) {
    if (in_array('--debug', $_SERVER['argv'], TRUE)) {
      // Write directly to STDOUT to not produce unexpected test output.
      // The STDOUT stream does not obey output buffering.
      fwrite(STDOUT, $message . "\n");
    }
  }

}
