<?php

namespace Drupal\KernelTests;

/**
 * Translates Simpletest assertion methods to PHPUnit.
 *
 * Protected methods are custom. Public static methods override methods of
 * \PHPUnit\Framework\Assert.
 *
 * Deprecated Scheduled for removal in Drupal 10.0.0. Use PHPUnit's native
 *   assert methods instead.
 *
 * @todo https://www.drupal.org/project/drupal/issues/3031580 Note that
 *   deprecations in this file do not use the @ symbol in Drupal 8 because this
 *   will be removed in Drupal 10.0.0.
 */
trait AssertLegacyTrait {

  /**
   * @see \Drupal\simpletest\TestBase::assert()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use self::assertTrue()
   *   instead.
   */
  protected function assert($actual, $message = '') {
    parent::assertTrue((bool) $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertEqual()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use self::assertEquals()
   *   instead.
   */
  protected function assertEqual($actual, $expected, $message = '') {
    $this->assertEquals($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotEqual()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use
   *   self::assertNotEquals() instead.
   */
  protected function assertNotEqual($actual, $expected, $message = '') {
    $this->assertNotEquals($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdentical()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use self::assertSame()
   *   instead.
   */
  protected function assertIdentical($actual, $expected, $message = '') {
    $this->assertSame($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotIdentical()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use
   *   self::assertNotSame() instead.
   */
  protected function assertNotIdentical($actual, $expected, $message = '') {
    $this->assertNotSame($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdenticalObject()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use self::assertEquals()
   *   instead.
   */
  protected function assertIdenticalObject($actual, $expected, $message = '') {
    // Note: ::assertSame checks whether its the same object. ::assertEquals
    // though compares

    $this->assertEquals($expected, $actual, (string) $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::pass()
   *
   * Deprecated Scheduled for removal in Drupal 10.0.0. Use self::assertTrue()
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
