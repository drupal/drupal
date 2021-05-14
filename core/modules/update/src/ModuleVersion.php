<?php

namespace Drupal\update;

/**
 * Provides a module version value object.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use
   *   \Drupal\Core\Extension\ExtensionVersion instead. As an internal class
 *   ExtensionVersion may also be removed in a minor release.
 *
 * @internal
 *
 * @see https://www.drupal.org/node/3095201
 */
final class ModuleVersion {

  /**
   * The '8.x-' prefix is used on contrib module version numbers.
   *
   * @var string
   */
  const CORE_PREFIX = '8.x-';

  /**
   * The major version.
   *
   * @var string
   */
  protected $majorVersion;

  /**
   * The version extra string.
   *
   * For example, if the module version is '2.0.3-alpha1', then the version
   * extra string is 'alpha1'.
   *
   * @var string|null
   */
  protected $versionExtra;

  /**
   * Constructs a module version object from a version string.
   *
   * @param string $version_string
   *   The version string.
   *
   * @return \Drupal\update\ModuleVersion
   *   The module version instance.
   *
   * @throws \UnexpectedValueException
   *   Thrown when a legacy version string has a core prefix other than "8.x-"
   *   for example, version strings such as "7.x-1.0" are not supported.
   */
  public static function createFromVersionString($version_string) {
    $original_version = $version_string;
    if (strpos($version_string, static::CORE_PREFIX) === 0 && $version_string !== '8.x-dev') {
      $version_string = preg_replace('/8\.x-/', '', $version_string, 1);
    }
    else {
      // Ensure the version string has no unsupported core prefixes.
      $dot_x_position = strpos($version_string, '.x-');
      if ($dot_x_position === 1 || $dot_x_position === 2) {
        $after_core_prefix = explode('.x-', $version_string)[1];
        if ($after_core_prefix !== 'dev') {
          throw new \UnexpectedValueException("Unexpected version core prefix in $version_string. The only core prefix expected in \Drupal\update\ModuleVersion is: 8.x-");
        }
      }
    }
    $version_parts = explode('.', $version_string);
    $major_version = $version_parts[0];
    $version_parts_count = count($version_parts);
    $last_part_split = explode('-', $version_parts[count($version_parts) - 1]);
    $version_extra = count($last_part_split) === 1 ? NULL : $last_part_split[1];
    if ($version_parts_count > 3 || $version_parts_count < 2
       || !is_numeric($major_version)
       || ($version_parts_count === 3 && !is_numeric($version_parts[1]))
      // The only case where a non-numeric version part other the extra part is
      // allowed is in development versions like 8.x-1.x-dev, 1.2.x-dev or
      // 1.x-dev.
       || (!is_numeric($last_part_split[0]) && $last_part_split !== 'x' && $version_extra !== 'dev')) {
      throw new \UnexpectedValueException("Unexpected version number in: $original_version");
    }
    return new static($major_version, $version_extra);
  }

  /**
   * Constructs a ModuleVersion object.
   *
   * @param string $major_version
   *   The major version.
   * @param string|null $version_extra
   *   The extra version string.
   */
  private function __construct($major_version, $version_extra) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:9.2.0 and will be removed before drupal:10.0.0. Use The \Drupal\Core\Extension\ExtensionVersion instead. As an internal class, ExtensionVersion may also be removed in a minor release.', E_USER_DEPRECATED);
    $this->majorVersion = $major_version;
    $this->versionExtra = $version_extra;
  }

  /**
   * Constructs a module version object from a support branch.
   *
   * This can be used to determine the major version of the branch.
   * ::getVersionExtra() will always return NULL for branches.
   *
   * @param string $branch
   *   The support branch.
   *
   * @return \Drupal\update\ModuleVersion
   *   The module version instance.
   *
   * @throws \UnexpectedValueException
   *   Thrown when $branch is not valid because it does not end in ".".
   */
  public static function createFromSupportBranch($branch) {
    if (substr($branch, -1) !== '.') {
      throw new \UnexpectedValueException("Invalid support branch: $branch");
    }
    return static::createFromVersionString($branch . '0');
  }

  /**
   * Gets the major version.
   *
   * @return string
   *   The major version.
   */
  public function getMajorVersion() {
    return $this->majorVersion;
  }

  /**
   * Gets the version extra string at the end of the version number.
   *
   * @return string|null
   *   The version extra string if available, or otherwise NULL.
   */
  public function getVersionExtra() {
    return $this->versionExtra;
  }

}
