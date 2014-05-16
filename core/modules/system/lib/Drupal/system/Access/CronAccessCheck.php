<?php

/**
 * @file
 * Contains Drupal\system\Access\CronAccessCheck.
 */

namespace Drupal\system\Access;

use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Access check for cron routes.
 */
class CronAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param string $key
   *   The cron key.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access($key) {
    if ($key != \Drupal::state()->get('system.cron_key')) {
      watchdog('cron', 'Cron could not run because an invalid key was used.', array(), WATCHDOG_NOTICE);
      return static::KILL;
    }
    elseif (\Drupal::state()->get('system.maintenance_mode')) {
      watchdog('cron', 'Cron could not run because the site is in maintenance mode.', array(), WATCHDOG_NOTICE);
      return static::KILL;
    }
    return static::ALLOW;
  }
}
