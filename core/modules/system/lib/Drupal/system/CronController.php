<?php

/**
 * @file
 * Definition of Drupal\system\CronController.
 */

namespace Drupal\system;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Cron handling.
 */
class CronController {

  /**
   * Run Cron once.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Symfony response object.
   */
  public function run() {
    // @todo Make this an injected object.
    drupal_cron_run();

    // HTTP 204 is "No content", meaning "I did what you asked and we're done."
    return new Response('', 204);
  }

  /**
   * Run cron manually.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A Symfony direct response object.
   */
  public function runManually() {
    if (drupal_cron_run()) {
      drupal_set_message(t('Cron ran successfully.'));
    }
    else {
      drupal_set_message(t('Cron run failed.'), 'error');
    }
    return new RedirectResponse(url('admin/reports/status', array('absolute' => TRUE)));
  }

}
