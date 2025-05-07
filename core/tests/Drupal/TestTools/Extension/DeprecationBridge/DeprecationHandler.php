<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

use PHPUnit\Framework\TestCase;

/**
 * Drupal's PHPUnit extension to manage code deprecation.
 *
 * This class is a replacement for symfony/phpunit-bridge that does not
 * support PHPUnit 10. In the future this extension might be dropped if
 * PHPUnit adds support for all deprecation management needs.
 *
 * @internal
 */
final class DeprecationHandler {

  /**
   * Indicates if the extension is enabled.
   */
  private static bool $enabled = FALSE;

  /**
   * A list of deprecation messages that should be ignored if detected.
   *
   * @var list<string>
   */
  private static array $deprecationIgnorePatterns = [];

  /**
   * A list of expected deprecation messages.
   *
   * @var list<string>
   */
  private static array $expectedDeprecations = [];

  /**
   * A list of deprecation messages collected during test run.
   *
   * @var list<string>
   */
  private static array $collectedDeprecations = [];

  /**
   * This class should not be instantiated.
   */
  private function __construct() {
    throw new \LogicException(__CLASS__ . ' should not be instantiated');
  }

  /**
   * Returns the extension configuration.
   *
   * For historical reasons, the configuration is stored in the
   * SYMFONY_DEPRECATIONS_HELPER environment variable.
   *
   * @return array|false
   *   An array of configuration variables, of FALSE if the extension is
   *   disabled.
   */
  public static function getConfiguration(): array|FALSE {
    $environmentVariable = getenv('SYMFONY_DEPRECATIONS_HELPER');
    if ($environmentVariable === 'disabled') {
      return FALSE;
    }
    if ($environmentVariable === FALSE) {
      // Ensure ignored deprecation patterns listed in .deprecation-ignore.txt
      // are considered in testing.
      $relativeFilePath = __DIR__ . "/../../../../../.deprecation-ignore.txt";
      $deprecationIgnoreFilename = realpath($relativeFilePath);
      if (empty($deprecationIgnoreFilename)) {
        throw new \InvalidArgumentException(sprintf('The ignoreFile "%s" does not exist.', $relativeFilePath));
      }
      $environmentVariable = "ignoreFile=$deprecationIgnoreFilename";
    }
    parse_str($environmentVariable, $configuration);

    $environmentVariable = getenv('PHPUNIT_FAIL_ON_PHPUNIT_DEPRECATION');
    $phpUnitDeprecationVariable = $environmentVariable !== FALSE ? $environmentVariable : TRUE;
    $configuration['failOnPhpunitDeprecation'] = filter_var($phpUnitDeprecationVariable, \FILTER_VALIDATE_BOOLEAN);

    return $configuration;
  }

  /**
   * Determines if the extension is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE if disabled.
   */
  public static function isEnabled(): bool {
    return self::$enabled;
  }

  /**
   * Initializes the extension.
   *
   * @param string|null $ignoreFile
   *   The path to a file containing ignore patterns for deprecations.
   */
  public static function init(?string $ignoreFile = NULL): void {
    if (self::isEnabled()) {
      throw new \LogicException(__CLASS__ . ' is already initialized');
    }

    // Load the deprecation ignore patterns from the specified file.
    if ($ignoreFile && !self::$deprecationIgnorePatterns) {
      if (!is_file($ignoreFile)) {
        throw new \InvalidArgumentException(sprintf('The ignoreFile "%s" does not exist.', $ignoreFile));
      }
      set_error_handler(static function ($t, $m) use ($ignoreFile, &$line) {
        throw new \RuntimeException(sprintf('Invalid pattern found in "%s" on line "%d"', $ignoreFile, 1 + $line) . substr($m, 12));
      });
      try {
        foreach (file($ignoreFile) as $line => $pattern) {
          if ((trim($pattern)[0] ?? '#') !== '#') {
            preg_match($pattern, '');
            self::$deprecationIgnorePatterns[] = $pattern;
          }
        }
      }
      finally {
        restore_error_handler();
      }
    }

    // Mark the extension as enabled.
    self::$enabled = TRUE;
  }

  /**
   * Resets the extension.
   *
   * The extension should be reset at the beginning of each test run to ensure
   * matching of expected and actual deprecations.
   */
  public static function reset(): void {
    if (!self::isEnabled()) {
      return;
    }
    self::$expectedDeprecations = [];
    self::$collectedDeprecations = [];
  }

  /**
   * Adds an expected deprecation.
   *
   * Tests will expect deprecations during the test execution; at the end of
   * each test run, collected deprecations are checked against the expected
   * ones.
   *
   * @param string $message
   *   The expected deprecation message.
   */
  public static function expectDeprecation(string $message): void {
    if (!self::isEnabled()) {
      return;
    }
    self::$expectedDeprecations[] = $message;
  }

  /**
   * Returns all expected deprecations.
   *
   * @return list<string>
   *   The expected deprecation messages.
   */
  public static function getExpectedDeprecations(): array {
    if (!self::isEnabled()) {
      throw new \LogicException(__CLASS__ . ' is not initialized');
    }
    return self::$expectedDeprecations;
  }

  /**
   * Collects an actual deprecation.
   *
   * Tests will expect deprecations during the test execution; at the end of
   * each test run, collected deprecations are checked against the expected
   * ones.
   *
   * @param string $message
   *   The actual deprecation message triggered via trigger_error().
   */
  public static function collectActualDeprecation(string $message): void {
    if (!self::isEnabled()) {
      return;
    }
    self::$collectedDeprecations[] = $message;
  }

  /**
   * Returns all collected deprecations.
   *
   * @return list<string>
   *   The collected deprecation messages.
   */
  public static function getCollectedDeprecations(): array {
    if (!self::isEnabled()) {
      throw new \LogicException(__CLASS__ . ' is not initialized');
    }
    return self::$collectedDeprecations;
  }

  /**
   * Determines if an actual deprecation should be ignored.
   *
   * Deprecations that match the patterns included in the ignore file should
   * be ignored.
   *
   * @param string $deprecationMessage
   *   The actual deprecation message triggered via trigger_error().
   */
  public static function isIgnoredDeprecation(string $deprecationMessage): bool {
    if (!self::$deprecationIgnorePatterns) {
      return FALSE;
    }
    $result = @preg_filter(self::$deprecationIgnorePatterns, '$0', $deprecationMessage);
    if (preg_last_error() !== \PREG_NO_ERROR) {
      throw new \RuntimeException(preg_last_error_msg());
    }
    return (bool) $result;
  }

  /**
   * Determines if a test case is a deprecation test.
   *
   * Deprecation tests are those that are annotated with '@group legacy' or
   * that have a '#[IgnoreDeprecations]' attribute.
   *
   * @param \PHPUnit\Framework\TestCase $testCase
   *   The test case being executed.
   */
  public static function isDeprecationTest(TestCase $testCase): bool {
    return $testCase->valueObjectForEvents()->metadata()->isIgnoreDeprecations()->isNotEmpty() || self::isTestInLegacyGroup($testCase);
  }

  /**
   * Determines if a test case is part of the 'legacy' group.
   *
   * @param \PHPUnit\Framework\TestCase $testCase
   *   The test case being executed.
   */
  private static function isTestInLegacyGroup(TestCase $testCase): bool {
    $groups = [];
    foreach ($testCase->valueObjectForEvents()->metadata()->isGroup() as $metadata) {
      $groups[] = $metadata->groupName();
    }
    return in_array('legacy', $groups, TRUE);
  }

}
