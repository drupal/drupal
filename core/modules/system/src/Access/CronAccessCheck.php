<?php

/**
 * @file
 * Contains Drupal\system\Access\CronAccessCheck.
 */

namespace Drupal\system\Access;

use Drupal\Core\Access\AccessResult;
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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($key) {
    if ($key != \Drupal::state()->get('system.cron_key')) {
      \Drupal::logger('cron')->notice('Cron could not run because an invalid key was used.');
      return AccessResult::forbidden()->setCacheable(FALSE);
    }
    elseif (\Drupal::state()->get('system.maintenance_mode')) {
      \Drupal::logger('cron')->notice('Cron could not run because the site is in maintenance mode.');
      return AccessResult::forbidden()->setCacheable(FALSE);
    }
    return AccessResult::allowed()->setCacheable(FALSE);
  }
}
