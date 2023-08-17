<?php

declare(strict_types=1);

namespace Drupal\Component\Utility;

/**
 * Provides a helper method for handling deprecated code paths in projects.
 */
final class DeprecationHelper {

  /**
   * Helper to run a callback based on the installed version of a project.
   *
   * With this helper, contributed or custom modules can run different code
   * paths based on the version of a project (e.g. Drupal) using callbacks.
   *
   * The below templates help code editors and PHPStan understand the return
   * value of this function.
   *
   * @template Current
   * @template Deprecated
   *
   * @param string $currentVersion
   *   Version to check against.
   * @param string $deprecatedVersion
   *   Version that deprecated the old code path.
   * @param callable(): Current $currentCallable
   *   Callback for the current version.
   * @param callable(): Deprecated $deprecatedCallable
   *   Callback for deprecated code path.
   *
   * @return Current|Deprecated
   */
  public static function backwardsCompatibleCall(string $currentVersion, string $deprecatedVersion, callable $currentCallable, callable $deprecatedCallable): mixed {
    // Normalize the version string when it's a dev version to the first point release of that minor. E.g. 10.2.x-dev
    // and 10.2-dev both translate to 10.2.0
    $normalizedVersion = str_ends_with($currentVersion, '-dev') ? str_replace(['.x-dev', '-dev'], '.0', $currentVersion) : $currentVersion;

    return version_compare($normalizedVersion, $deprecatedVersion, '>=') ? $currentCallable() : $deprecatedCallable();
  }

}
