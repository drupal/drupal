<?php

/**
 * @file
 * Contains Drupal\system\Access\CronAccessCheck.
 */

namespace Drupal\system\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for cron routes.
 */
class CronAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_system_cron');
  }

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    $key = $request->attributes->get('key');
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
