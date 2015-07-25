<?php

/**
 * @file
 * Contains \Drupal\Core\CronInterface.
 */

namespace Drupal\Core;

/**
 * An interface for running cron tasks.
 *
 * @see https://www.drupal.org/cron
 */
interface CronInterface {

  /**
   * Executes a cron run.
   *
   * Do not call this function from a test. Use $this->cronRun() instead.
   *
   * @return bool
   *   TRUE upon success, FALSE otherwise.
   */
  public function run();

}
