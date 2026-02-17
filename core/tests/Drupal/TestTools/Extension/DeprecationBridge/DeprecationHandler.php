<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

/**
 * Drupal's PHPUnit extension to manage code deprecation.
 *
 * In the future this extension might be dropped if PHPUnit adds support for
 * ignoring a specified list of deprecations.
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
      set_error_handler(static function ($t, $m) use ($ignoreFile, &$line): void {
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

}
