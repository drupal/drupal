<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit6;

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
  public static function assertTrue($actual, $message = '') {
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
  public static function assertFalse($actual, $message = '') {
    if (is_bool($actual)) {
      parent::assertFalse($actual, $message);
    }
    else {
      @trigger_error('Support for asserting against non-boolean values in ::assertFalse is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use a different assert method, for example, ::assertEmpty(). See https://www.drupal.org/node/3082086', E_USER_DEPRECATED);
      parent::assertEmpty($actual, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function assertEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE) {
    // Cast objects implementing MarkupInterface to string instead of
    // relying on PHP casting them to string depending on what they are being
    // comparing with.
    if (method_exists(self::class, 'castSafeStrings')) {
      $expected = self::castSafeStrings($expected);
      $actual = self::castSafeStrings($actual);
    }
    parent::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
  }

  /**
   * Forward compatibility for assertEqualsCanonicalizing.
   */
  public static function assertEqualsCanonicalizing($expected, $actual, $message = '') {
    static::assertEquals($expected, $actual, $message, 0.0, 10, TRUE);
  }

  /**
   * Forward compatibility for assertNotEqualsCanonicalizing.
   */
  public static function assertNotEqualsCanonicalizing($expected, $actual, $message = '') {
    static::assertNotEquals($expected, $actual, $message, 0.0, 10, TRUE);
  }

  /**
   * Forward compatibility for assertStringContainsString.
   */
  public static function assertStringContainsString($needle, $haystack, $message = '') {
    static::assertContains((string) $needle, (string) $haystack, $message);
  }

  /**
   * Forward compatibility for assertStringContainsStringIgnoringCase.
   */
  public static function assertStringContainsStringIgnoringCase($needle, $haystack, $message = '') {
    static::assertContains((string) $needle, (string) $haystack, $message, TRUE);
  }

  /**
   * Forward compatibility for assertStringNotContainsString.
   */
  public static function assertStringNotContainsString($needle, $haystack, $message = '') {
    static::assertNotContains((string) $needle, (string) $haystack, $message);
  }

  /**
   * Forward compatibility for assertStringNotContainsStringIgnoringCase.
   */
  public static function assertStringNotContainsStringIgnoringCase($needle, $haystack, $message = '') {
    static::assertNotContains((string) $needle, (string) $haystack, $message, TRUE);
  }

  /**
   * Provides forward-compatibility for assertIsArray().
   */
  public static function assertIsArray($actual, $message = '') {
    static::assertInternalType('array', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsBool().
   */
  public static function assertIsBool($actual, $message = '') {
    static::assertInternalType('bool', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsFloat().
   */
  public static function assertIsFloat($actual, $message = '') {
    static::assertInternalType('float', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsInt().
   */
  public static function assertIsInt($actual, $message = '') {
    static::assertInternalType('int', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNumeric().
   */
  public static function assertIsNumeric($actual, $message = '') {
    static::assertInternalType('numeric', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsObject().
   */
  public static function assertIsObject($actual, $message = '') {
    static::assertInternalType('object', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsResource().
   */
  public static function assertIsResource($actual, $message = '') {
    static::assertInternalType('resource', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsString().
   */
  public static function assertIsString($actual, $message = '') {
    static::assertInternalType('string', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsScalar().
   */
  public static function assertIsScalar($actual, $message = '') {
    static::assertInternalType('scalar', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsCallable().
   */
  public static function assertIsCallable($actual, $message = '') {
    static::assertInternalType('callable', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotArray().
   */
  public static function assertIsNotArray($actual, $message = '') {
    static::assertNotInternalType('array', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotBool().
   */
  public static function assertIsNotBool($actual, $message = '') {
    static::assertNotInternalType('bool', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotFloat().
   */
  public static function assertIsNotFloat($actual, $message = '') {
    static::assertNotInternalType('float', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotInt().
   */
  public static function assertIsNotInt($actual, $message = '') {
    static::assertNotInternalType('int', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotNumeric().
   */
  public static function assertIsNotNumeric($actual, $message = '') {
    static::assertNotInternalType('numeric', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotObject().
   */
  public static function assertIsNotObject($actual, $message = '') {
    static::assertNotInternalType('object', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotResource().
   */
  public static function assertIsNotResource($actual, $message = '') {
    static::assertNotInternalType('resource', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotString().
   */
  public static function assertIsNotString($actual, $message = '') {
    static::assertNotInternalType('string', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotScalar().
   */
  public static function assertIsNotScalar($actual, $message = '') {
    static::assertNotInternalType('scalar', $actual, $message);
  }

  /**
   * Provides forward-compatibility for assertIsNotCallable().
   */
  public static function assertIsNotCallable($actual, $message = '') {
    static::assertNotInternalType('callable', $actual, $message);
  }

}
