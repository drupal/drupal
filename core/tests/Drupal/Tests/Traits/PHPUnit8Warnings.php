<?php

namespace Drupal\Tests\Traits;

/**
 * Used to ignore warnings being added by PHPUnit 8.
 *
 * This trait exists to allow Drupal 8 tests using PHPUnit 7 and Drupal 9 tests
 * using PHPUnit 8 to happily co-exist. Once Drupal 8 and Drupal 9 are not so
 * closely aligned these will be fixed in core and the warnings will be emitted
 * from the test runner.
 *
 * @todo https://www.drupal.org/project/drupal/issues/3110543 Remove the ignored
 *   warnings to support PHPUnit 9.
 *
 * @internal
 */
trait PHPUnit8Warnings {

  /**
   * The list of warnings to ignore.
   *
   * @var string[]
   */
  private static $ignoredWarnings = [
    'expectExceptionMessageRegExp() is deprecated in PHPUnit 8 and will be removed in PHPUnit 9.',
  ];

  /**
   * Ignores specific PHPUnit 8 warnings.
   *
   * @see \PHPUnit\Framework\TestCase::addWarning()
   *
   * @internal
   */
  public function addWarning(string $warning): void {
    if (in_array($warning, self::$ignoredWarnings, TRUE)) {
      return;
    }
    parent::addWarning($warning);
  }

}
