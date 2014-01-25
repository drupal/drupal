<?php

/**
 * Contains \Drupal\Core\CronInterface.
 */

namespace Drupal\Core;

/**
 * An interface for running cron tasks.
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
