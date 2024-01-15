<?php

declare(strict_types=1);

namespace Drupal\TestTools\PhpUnitCompatibility;

use PHPUnit\Runner\Version;

/**
 * Helper class to determine information about running PHPUnit version.
 *
 * This class contains static methods only and is not meant to be instantiated.
 */
final class RunnerVersion {

  /**
   * This class should not be instantiated.
   */
  private function __construct() {
  }

  /**
   * Returns the major version of the PHPUnit runner being used.
   *
   * @return int
   *   The major version of the PHPUnit runner being used.
   */
  public static function getMajor() {
    return (int) explode('.', Version::id())[0];
  }

}
