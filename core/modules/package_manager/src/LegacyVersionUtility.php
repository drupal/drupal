<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\Extension\ExtensionVersion;

/**
 * A utility class for dealing with legacy version numbers.
 *
 * @internal
 *   This is an internal utility class that could be changed or removed in any
 *   release and should not be used by external code.
 */
final class LegacyVersionUtility {

  /**
   * Converts a version number to a semantic version if needed.
   *
   * @param string $version
   *   The version number.
   *
   * @return string
   *   The version number, converted if needed.
   */
  public static function convertToSemanticVersion(string $version): string {
    if (self::isLegacyVersion($version)) {
      $version = substr($version, 4);
      $version_parts = explode('-', $version);
      $version = $version_parts[0] . '.0';
      if (count($version_parts) === 2) {
        $version .= '-' . $version_parts[1];
      }
    }
    return $version;
  }

  /**
   * Converts a version number to a legacy version if needed and possible.
   *
   * @param string $version_string
   *   The version number.
   *
   * @return string|null
   *   The version number, converted if needed, or NULL if not possible. Only
   *   semantic version numbers that have patch level of 0 can be converted into
   *   legacy version numbers.
   */
  public static function convertToLegacyVersion($version_string): ?string {
    if (self::isLegacyVersion($version_string)) {
      return $version_string;
    }
    $version = ExtensionVersion::createFromVersionString($version_string);
    if ($extra = $version->getVersionExtra()) {
      $version_string_without_extra = str_replace("-$extra", '', $version_string);
    }
    else {
      $version_string_without_extra = $version_string;
    }
    [,, $patch] = explode('.', $version_string_without_extra);
    // A semantic version can only be converted to legacy if it's patch level is
    // '0'.
    if ($patch !== '0') {
      return NULL;
    }
    return '8.x-' . $version->getMajorVersion() . '.' . $version->getMinorVersion() . ($extra ? "-$extra" : '');
  }

  /**
   * Determines if a version is legacy.
   *
   * @param string $version
   *   The version number.
   *
   * @return bool
   *   TRUE if the version is a legacy version number, otherwise FALSE.
   */
  private static function isLegacyVersion(string $version): bool {
    return stripos($version, '8.x-') === 0;
  }

}
