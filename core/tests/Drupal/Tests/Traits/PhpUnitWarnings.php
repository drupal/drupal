<?php

namespace Drupal\Tests\Traits;

/**
 * Converts deprecation warnings added by PHPUnit to silenced deprecations.
 *
 * This trait exists to allow Drupal to run tests with multiple versions of
 * PHPUnit without failing due to PHPUnit's deprecation warnings.
 *
 * @internal
 */
trait PhpUnitWarnings {

  /**
   * Deprecation warnings from PHPUnit to raise with @trigger_error().
   *
   * Add any PHPUnit deprecations that should be handled as deprecation warnings
   * (rather than unconditional failures) for core and contrib.
   *
   * @var string[]
   */
  private static $deprecationWarnings = [
    'Using assertContains() with string haystacks is deprecated and will not be supported in PHPUnit 9. Refactor your test to use assertStringContainsString() or assertStringContainsStringIgnoringCase() instead.',
    'Using assertNotContains() with string haystacks is deprecated and will not be supported in PHPUnit 9. Refactor your test to use assertStringNotContainsString() or assertStringNotContainsStringIgnoringCase() instead.',
    'assertArraySubset() is deprecated and will be removed in PHPUnit 9.',
    'assertInternalType() is deprecated and will be removed in PHPUnit 9. Refactor your test to use assertIsArray(), assertIsBool(), assertIsFloat(), assertIsInt(), assertIsNumeric(), assertIsObject(), assertIsResource(), assertIsString(), assertIsScalar(), assertIsCallable(), or assertIsIterable() instead.',
    'readAttribute() is deprecated and will be removed in PHPUnit 9.',
    'getObjectAttribute() is deprecated and will be removed in PHPUnit 9.',
    'The optional $canonicalize parameter of assertEquals() is deprecated and will be removed in PHPUnit 9. Refactor your test to use assertEqualsCanonicalizing() instead.',
    'assertAttributeEquals() is deprecated and will be removed in PHPUnit 9.',
    'assertAttributeSame() is deprecated and will be removed in PHPUnit 9.',
    'assertAttributeInstanceOf() is deprecated and will be removed in PHPUnit 9.',
    'assertAttributeEmpty() is deprecated and will be removed in PHPUnit 9.',
    'The optional $ignoreCase parameter of assertContains() is deprecated and will be removed in PHPUnit 9.',
    'The optional $ignoreCase parameter of assertNotContains() is deprecated and will be removed in PHPUnit 9.',
    'expectExceptionMessageRegExp() is deprecated in PHPUnit 8 and will be removed in PHPUnit 9.',
    // Warning for testing.
    'Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()',
  ];

  /**
   * Converts PHPUnit deprecation warnings to E_USER_DEPRECATED.
   *
   * @param string $warning
   *   The warning message raised in tests.
   *
   * @see \PHPUnit\Framework\TestCase::addWarning()
   *
   * @internal
   */
  public function addWarning(string $warning): void {
    if (in_array($warning, self::$deprecationWarnings, TRUE)) {
      // Convert listed PHPUnit deprecations into E_USER_DEPRECATED and prevent
      // each from being raised as a test warning.
      @trigger_error($warning, E_USER_DEPRECATED);
      return;
    }

    // Otherwise, let the parent raise any warning not specifically listed.
    parent::addWarning($warning);
  }

}
