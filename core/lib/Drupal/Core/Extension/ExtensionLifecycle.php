<?php

namespace Drupal\Core\Extension;

/**
 * Extension lifecycle.
 *
 * The lifecycle of an extension (module/theme etc) can go through the following
 * progression:
 * 1. Starts "experimental".
 * 2. Stabilizes and goes "stable".
 * 3. Eventually (maybe), becomes "deprecated" when being phased out.
 * 4. Finally (maybe), becomes "obsolete" and can't be enabled anymore.
 */
final class ExtensionLifecycle {

  /**
   * The string used to identify the lifecycle in an .info.yml file.
   */
  const LIFECYCLE_IDENTIFIER = 'lifecycle';

  /**
   * The string used to identify the lifecycle link in an .info.yml file.
   */
  const LIFECYCLE_LINK_IDENTIFIER = 'lifecycle_link';

  /**
   * Extension is experimental. Warnings will be shown if installed.
   */
  const EXPERIMENTAL = 'experimental';

  /**
   * Extension is stable. This is the default value of any extension.
   */
  const STABLE = 'stable';

  /**
   * Extension is deprecated. Warnings will be shown if still installed.
   */
  const DEPRECATED = 'deprecated';

  /**
   * Extension is obsolete and installation will be prevented.
   */
  const OBSOLETE = 'obsolete';

  /**
   * Determines if a given extension lifecycle string is valid.
   *
   * @param string $lifecycle
   *   The lifecycle to validate.
   *
   * @return bool
   *   TRUE if the lifecycle is valid, otherwise FALSE.
   */
  public static function isValid(string $lifecycle) : bool {
    $valid_values = [
      self::EXPERIMENTAL,
      self::STABLE,
      self::DEPRECATED,
      self::OBSOLETE,
    ];
    return in_array($lifecycle, $valid_values, TRUE);
  }

}
