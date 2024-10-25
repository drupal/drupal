<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use Composer\Autoload\ClassLoader;

/**
 * Asserts preconditions for tests to function properly.
 */
trait AssertPreconditionsTrait {

  /**
   * Invokes the test preconditions assertion before the first test is run.
   *
   * "Use" this trait on any Package Manager test class that directly extends a
   * Core test class, i.e., any class that does NOT extend a test class in a
   * Package Manager test namespace. If that class implements this method, too,
   * be sure to call this first thing in it.
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    static::failIfUnmetPreConditions('before');
  }

  /**
   * Invokes the test preconditions assertion after each test run.
   *
   * This ensures that no test method leaves behind violations of test
   * preconditions. This makes it trivial to discover broken tests.
   */
  protected function tearDown(): void {
    parent::tearDown();
    static::failIfUnmetPreConditions('after');
  }

  /**
   * Asserts universal test preconditions before any setup is done.
   *
   * If these preconditions aren't met, automated tests will absolutely fail
   * needlessly with misleading errors. In that case, there's no reason to even
   * begin.
   *
   * Ordinarily, these preconditions would be asserted in
   * ::assertPreConditions(), which PHPUnit provides for exactly this use case.
   * Unfortunately, that method doesn't run until after ::setUp(), so our (many)
   * tests with expensive, time-consuming setup routines wouldn't actually fail
   * very early.
   *
   * @param string $when
   *   Either 'before' (before any test methods run) or 'after' (after any test
   *   method finishes).
   *
   * @see \PHPUnit\Framework\TestCase::assertPreConditions()
   * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
   * @see self::setupBeforeClass()
   * @see self::tearDown()
   */
  protected static function failIfUnmetPreConditions(string $when): void {
    assert(in_array($when, ['before', 'after'], TRUE));
    static::assertNoFailureMarker($when);
  }

  /**
   * Asserts that there is no failure marker present.
   *
   * @param string $when
   *   Either 'before' (before any test methods run) or 'after' (after any test
   *   method finishes).
   *
   * @see \Drupal\package_manager\FailureMarker
   */
  private static function assertNoFailureMarker(string $when): void {
    // If the failure marker exists, it will be in the project root. The project
    // root is defined as the directory containing the `vendor` directory.
    // @see \Drupal\package_manager\FailureMarker::getPath()
    $failure_marker = static::getProjectRoot() . '/PACKAGE_MANAGER_FAILURE.yml';
    if (file_exists($failure_marker)) {
      $suffix = $when === 'before'
        ? 'Remove it to continue.'
        : 'This test method created this marker but failed to clean up after itself.';
      static::fail("The failure marker '$failure_marker' is present in the project. $suffix");
    }
  }

  /**
   * Returns the absolute path of the project root.
   *
   * @return string
   *   The absolute path of the project root.
   *
   * @see \Drupal\package_manager\PathLocator::getProjectRoot()
   */
  private static function getProjectRoot(): string {
    // This is tricky, because this method has to be static (since
    // ::setUpBeforeClass is), so it can't just get the container from an
    // instance member.
    // Use reflection to extract the vendor directory from the class loader.
    $class_loaders = ClassLoader::getRegisteredLoaders();
    $vendor_directory = key($class_loaders);
    return dirname($vendor_directory);
  }

}
