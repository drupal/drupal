<?php

namespace Drupal\Core\Extension;

/**
 * Extension lifecycle.
 *
 * The lifecycle of a core module can go through the following progression:
 * 1. Starts "experimental".
 * 2. Stabilizes and goes "normal".
 * 3. Eventually (maybe), becomes "deprecated" on the way out of core.
 * 4. Finally (maybe), becomes "obsolete" and can't be enabled anymore.
 */
final class ExtensionLifecycle {

  /**
   * Extension is experimental. Warnings will be shown if installed.
   */
  const EXPERIMENTAL = 'experimental';

  /**
   * Extension is normal. This is the default value of any extension.
   */
  const NORMAL = 'normal';

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
  public static function isValid($lifecycle) : bool {
    $valid_lifecycles = [
      self::EXPERIMENTAL,
      self::NORMAL,
      self::DEPRECATED,
      self::OBSOLETE,
    ];
    return in_array($lifecycle, $valid_lifecycles, TRUE);
  }

}
