<?php

/**
 * @file
 * Definition of Drupal\system\CronController.
 */

namespace Drupal\system;

use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for Cron handling.
 */
class CronController {

  /**
   * Run Cron once.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   A Symfony response object.
   */
  public function run() {
    // @todo Make this an injected object.
    drupal_cron_run();

    // HTTP 204 is "No content", meaning "I did what you asked and we're done."
    return new Response('', 204);
  }
}
