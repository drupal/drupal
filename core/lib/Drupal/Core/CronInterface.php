<?php

namespace Drupal\Core;

/**
 * An interface for running cron tasks.
 *
 * @see https://www.drupal.org/cron
 */
interface CronInterface {

  /**
   * The default time cron should execute each queue in seconds.
   */
  public const DEFAULT_QUEUE_CRON_TIME = 15;

  /**
   * The default lease time a queue item should get when called from cron.
   */
  public const DEFAULT_QUEUE_CRON_LEASE_TIME = 30;

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
