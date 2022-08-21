<?php

namespace Drupal\Composer\Util;

/**
 * Utility methods for manipulating semantic versions.
 */
class SemanticVersion {

  /**
   * Given a version, generate a loose ^major.minor constraint.
   *
   * @param string $version
   *   Semantic version string. Example: 9.5.0-beta23.
   *
   * @return string
   *   Constraint string for major and minor. Example: ^9.5
   */
  public static function majorMinorConstraint(string $version): string {
    preg_match('/^(\d+)\.(\d+)\.\d+/', $version, $matches);
    return '^' . $matches[1] . '.' . $matches[2];
  }

}
