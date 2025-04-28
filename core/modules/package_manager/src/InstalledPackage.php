<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Component\Serialization\Yaml;

/**
 * A value object that represents an installed Composer package.
 */
final class InstalledPackage {

  /**
   * Constructs an InstalledPackage object.
   *
   * @param string $name
   *   The package name.
   * @param string $version
   *   The package version.
   * @param string|null $path
   *   The package path, or NULL if the package type is `metapackage`.
   * @param string $type
   *   The package type.
   */
  private function __construct(
    public readonly string $name,
    public readonly string $version,
    public readonly ?string $path,
    public readonly string $type,
  ) {}

  /**
   * Create an installed package object from an array.
   *
   * @param array $data
   *   The package data.
   *
   * @return static
   */
  public static function createFromArray(array $data): static {
    $path = isset($data['path']) ? realpath($data['path']) : NULL;
    // Fall back to `library`.
    // @see https://getcomposer.org/doc/04-schema.md#type
    $type = $data['type'] ?? 'library';
    assert(($type === 'metapackage') === is_null($path), 'Metapackage install path must be NULL.');

    return new static($data['name'], $data['version'], $path, $type);
  }

  /**
   * Returns the Drupal project name for this package.
   *
   * This assumes that drupal.org adds a `project` key to every `.info.yml` file
   * in the package, regardless of where they are in the package's directory
   * structure. The package name is irrelevant except for checking that the
   * vendor is `drupal`. For example, if the project key in the info file were
   * `my_module`, and the package name were `drupal/whatever`, and this method
   * would return `my_module`.
   *
   * @return string|null
   *   The name of the Drupal project installed by this package, or NULL if:
   *   - The package type is not one of `drupal-module`, `drupal-theme`, or
   *     `drupal-profile`.
   *   - The package's vendor is not `drupal`.
   *   - The project name could not otherwise be determined.
   *
   * @throws \UnexpectedValueException
   *   Thrown if the same project name exists in more than one package.
   */
  public function getProjectName(): ?string {
    // Only consider packages which are packaged by drupal.org and will be
    // known to the core Update Status module.
    $drupal_package_types = [
      'drupal-module',
      'drupal-theme',
      'drupal-profile',
    ];
    if ($this->path && str_starts_with($this->name, 'drupal/') && in_array($this->type, $drupal_package_types, TRUE)) {
      return $this->scanForProjectName();
    }
    return NULL;
  }

  /**
   * Scans a given path to determine the Drupal project name.
   *
   * The path will be scanned recursively for `.info.yml` files containing a
   * `project` key.
   *
   * @return string|null
   *   The name of the project, as declared in the first found `.info.yml` which
   *   contains a `project` key, or NULL if none was found.
   */
  private function scanForProjectName(): ?string {
    $iterator = new \RecursiveDirectoryIterator($this->path);
    $iterator = new \RecursiveIteratorIterator($iterator);
    $iterator = new \RegexIterator($iterator, '/.+\.info\.yml$/', \RegexIterator::GET_MATCH);

    foreach ($iterator as $match) {
      $info = file_get_contents($match[0]);
      $info = Yaml::decode($info);

      if (!empty($info['project'])) {
        return $info['project'];
      }
    }
    return NULL;
  }

}
