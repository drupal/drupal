<?php

/**
 * @file
 * Contains \Drupal\Tests\AssertLegacyTrait.
 */

namespace Drupal\Tests;

/**
 * Translates Simpletest assertion methods to PHPUnit.
 */
trait AssertLegacyTrait {

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
   */
  protected function assertEqual($actual, $expected, $message = '') {
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotEqual()
   */
  protected function assertNotEqual($actual, $expected, $message = '') {
    $this->assertNotEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdentical()
   */
  protected function assertIdentical($actual, $expected, $message = '') {
    $this->assertSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotIdentical()
   */
  protected function assertNotIdentical($actual, $expected, $message = '') {
    $this->assertNotSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdenticalObject()
   */
  protected function assertIdenticalObject($actual, $expected, $message = '') {
    $this->assertSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::pass()
   */
  protected function pass($message) {
    $this->assertTrue(TRUE, $message);
  }

}
