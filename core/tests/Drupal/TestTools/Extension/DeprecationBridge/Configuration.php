<?php
// @todo remove the ignore directive once PHPCS will support hooked properties.
// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

/**
 * Configuration for DeprecationHandler.
 *
 * @internal
 */
final class Configuration {

  /**
   * The class singleton.
   */
  private static self $instance;

  /**
   * Indicates if the project deprecation ignores are enabled.
   */
  public private(set) bool $projectIgnoresEnabled = FALSE;

  /**
   * Path to the deprecation ignore file.
   */
  public private(set) string $projectIgnoreFile;

  /**
   * Indicates if the debug class loader is enabled.
   */
  public private(set) bool $debugClassLoaderEnabled = FALSE;

  /**
   * The list of deprecation message patterns that should be ignored.
   *
   * @var list<string>
   */
  public private(set) array $deprecationIgnorePatterns = [];

  /**
   * Returns the singleton.
   *
   * @return self
   *   The singleton.
   */
  public static function instance(): self {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Loads the configuration from an array.
   *
   * Overwrites each configuration key that is already existing.
   *
   * @param array<string,bool|string|list<string>> $parameters
   *   An array of configuration elements.
   */
  public function load(array $parameters): void {
    if (array_key_exists('enableProjectIgnores', $parameters)) {
      $this->projectIgnoresEnabled = filter_var($parameters['enableProjectIgnores'] ?? FALSE, \FILTER_VALIDATE_BOOLEAN);
    }
    if (array_key_exists('projectIgnoreFile', $parameters)) {
      $this->projectIgnoreFile = $parameters['projectIgnoreFile'];
    }
    if (array_key_exists('enableDebugClassLoader', $parameters)) {
      $this->debugClassLoaderEnabled = filter_var($parameters['enableDebugClassLoader'] ?? FALSE, \FILTER_VALIDATE_BOOLEAN);
    }
  }

  /**
   * Loads the deprecation ignore patterns from the ignore file.
   */
  public function loadDeprecationIgnorePatterns(): void {
    assert(empty($this->deprecationIgnorePatterns), 'deprecationIgnorePatterns can only be loaded once');

    // Load the deprecation ignore patterns from the specified file.
    $path = $this->absoluteAndExistingPath($this->projectIgnoreFile);
    set_error_handler(static function () use ($path, &$line): never {
      throw new \RuntimeException(sprintf('Invalid pattern found in "%s" on line %d', $path, 1 + $line));
    });
    try {
      foreach (file($path) as $line => $pattern) {
        if ((trim($pattern)[0] ?? '#') !== '#') {
          preg_match($pattern, '');
          $this->deprecationIgnorePatterns[] = $pattern;
        }
      }
    }
    finally {
      restore_error_handler();
    }
  }

  /**
   * Returns an absolute file path, resolving relative ones from Drupal root.
   *
   * @param string $path
   *   A file path.
   *
   * @return string
   *   An absolute file path.
   */
  private function absoluteAndExistingPath(string $path): string {
    if (!str_starts_with($path, \DIRECTORY_SEPARATOR)) {
      $realPath = realpath(dirname(__DIR__, 6) . \DIRECTORY_SEPARATOR . $path);
    }
    else {
      $realPath = $path;
    }
    if ($realPath === FALSE || !is_file($realPath)) {
      throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $path));
    }
    return $realPath;
  }

}
