<?php

namespace Drupal\Component\Version;

use Composer\Semver\Semver;

/**
 * A utility class for semantic version comparison.
 */
class DrupalSemver {

  /**
   * Determines if a version satisfies the given constraints.
   *
   * This method uses \Composer\Semver\Semver::satisfies() but returns FALSE if
   * the version or constraints are not valid instead of throwing an exception.
   *
   * @param string $version
   *   The version.
   * @param string $constraints
   *   The constraints.
   *
   * @return bool
   *   TRUE if the version satisfies the constraints.
   *
   * @see \Composer\Semver\Semver::satisfies()
   */
  public static function satisfies($version, $constraints) {
    try {
      return Semver::satisfies($version, $constraints);
    }
    catch (\UnexpectedValueException $exception) {
      return FALSE;
    }
  }

}
