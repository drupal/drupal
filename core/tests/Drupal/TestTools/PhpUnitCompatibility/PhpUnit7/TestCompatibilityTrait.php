<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit7;

/**
 * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
 */
trait TestCompatibilityTrait {

  /**
   * @todo deprecate this method override in
   *   https://www.drupal.org/project/drupal/issues/2742585
   *
   * @see \Drupal\simpletest\TestBase::assertTrue()
   */
  public static function assertTrue($actual, string $message = ''): void {
    if (is_bool($actual)) {
      parent::assertTrue($actual, $message);
    }
    else {
      @trigger_error('Support for asserting against non-boolean values in ::assertTrue is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use a different assert method, for example, ::assertNotEmpty(). See https://www.drupal.org/node/3082086', E_USER_DEPRECATED);
      parent::assertNotEmpty($actual, $message);
    }
  }

  /**
   * @todo deprecate this method override in
   *   https://www.drupal.org/project/drupal/issues/2742585
   *
   * @see \Drupal\simpletest\TestBase::assertFalse()
   */
  public static function assertFalse($actual, string $message = ''): void {
    if (is_bool($actual)) {
      parent::assertFalse($actual, $message);
    }
    else {
      @trigger_error('Support for asserting against non-boolean values in ::assertFalse is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use a different assert method, for example, ::assertEmpty(). See https://www.drupal.org/node/3082086', E_USER_DEPRECATED);
      parent::assertEmpty($actual, $message);
    }
  }

  /**
   * Forward compatibility for assertStringContainsString.
   */
  public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void {
    static::assertContains($needle, $haystack, $message);
  }

  /**
   * Forward compatibility for assertStringContainsStringIgnoringCase.
   */
  public static function assertStringContainsStringIgnoringCase(string $needle, string $haystack, string $message = ''): void {
    static::assertContains($needle, $haystack, $message, TRUE);
  }

  /**
   * Forward compatibility for assertStringNotContainsString.
   */
  public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void {
    static::assertNotContains($needle, $haystack, $message);
  }

  /**
   * Forward compatibility for assertStringNotContainsStringIgnoringCase.
   */
  public static function assertStringNotContainsStringIgnoringCase(string $needle, string $haystack, string $message = ''): void {
    static::assertNotContains($needle, $haystack, $message, TRUE);
  }

  /**
   * Forward compatibility for assertEqualsCanonicalizing.
   */
  public static function assertEqualsCanonicalizing($expected, $actual, string $message = ''): void {
    static::assertEquals($expected, $actual, $message, 0.0, 10, TRUE);
  }

  /**
   * Forward compatibility for assertNotEqualsCanonicalizing.
   */
  public static function assertNotEqualsCanonicalizing($expected, $actual, string $message = ''): void {
    static::assertNotEquals($expected, $actual, $message, 0.0, 10, TRUE);
  }

  /**
   * Provides forward-compatibility for assertIsArray().
   */
  public static function assertIsArray($actual, string $message = ''): void {
    static::assertInternalType('array', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsBool().
   */
  public static function assertIsBool($actual, string $message = ''): void {
    static::assertInternalType('bool', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsFloat().
   */
  public static function assertIsFloat($actual, string $message = ''): void {
    static::assertInternalType('float', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsInt().
   */
  public static function assertIsInt($actual, string $message = ''): void {
    static::assertInternalType('int', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNumeric().
   */
  public static function assertIsNumeric($actual, string $message = ''): void {
    static::assertInternalType('numeric', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsObject().
   */
  public static function assertIsObject($actual, string $message = ''): void {
    static::assertInternalType('object', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsResource().
   */
  public static function assertIsResource($actual, string $message = ''): void {
    static::assertInternalType('resource', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsString().
   */
  public static function assertIsString($actual, string $message = ''): void {
    static::assertInternalType('string', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsScalar().
   */
  public static function assertIsScalar($actual, string $message = ''): void {
    static::assertInternalType('scalar', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsCallable().
   */
  public static function assertIsCallable($actual, string $message = ''): void {
    static::assertInternalType('callable', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotArray().
   */
  public static function assertIsNotArray($actual, string $message = ''): void {
    static::assertNotInternalType('array', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotBool().
   */
  public static function assertIsNotBool($actual, string $message = ''): void {
    static::assertNotInternalType('bool', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotFloat().
   */
  public static function assertIsNotFloat($actual, string $message = ''): void {
    static::assertNotInternalType('float', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotInt().
   */
  public static function assertIsNotInt($actual, string $message = ''): void {
    static::assertNotInternalType('int', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotNumeric().
   */
  public static function assertIsNotNumeric($actual, string $message = ''): void {
    static::assertNotInternalType('numeric', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotObject().
   */
  public static function assertIsNotObject($actual, string $message = ''): void {
    static::assertNotInternalType('object', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotResource().
   */
  public static function assertIsNotResource($actual, string $message = ''): void {
    static::assertNotInternalType('resource', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotString().
   */
  public static function assertIsNotString($actual, string $message = ''): void {
    static::assertNotInternalType('string', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotScalar().
   */
  public static function assertIsNotScalar($actual, string $message = ''): void {
    static::assertNotInternalType('scalar', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotCallable().
   */
  public static function assertIsNotCallable($actual, string $message = ''): void {
    static::assertNotInternalType('callable', $actual, $message);
  }

}
