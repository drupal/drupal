<?php

/**
 * @file
 * Definition of Drupal\system\CronController.
 */

namespace Drupal\system;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controllers for Cron handling.
 */
class CronController {

  /**
   * Run Cron once.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   A Symfony response object.
   */
  public function run($key) {
    if (!$this->access($key)) {
      throw new AccessDeniedHttpException();
    }

    // @todo Make this an injected object.
    drupal_cron_run();

    // HTTP 204 is "No content", meaning "I did what you asked and we're done."
    return new Response('', 204);
  }

  /**
   * Determines if the user has access to run cron.
   *
   * @todo Eliminate this method in favor of a new-style access checker once
   * http://drupal.org/node/1793520 gets in.
   */
  function access($key) {
    if ($key != state()->get('system.cron_key')) {
      watchdog('cron', 'Cron could not run because an invalid key was used.', array(), WATCHDOG_NOTICE);
      return FALSE;
    }
    elseif (config('system.maintenance')->get('enabled')) {
      watchdog('cron', 'Cron could not run because the site is in maintenance mode.', array(), WATCHDOG_NOTICE);
      return FALSE;
    }

    return TRUE;
  }

}
