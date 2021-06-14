<?php

namespace Drupal\Tests\Traits\Core;

/**
 * Adds ability to run cron from tests.
 */
trait CronRunTrait {

  /**
   * Runs cron on the test site.
   */
  protected function cronRun() {
    $this->drupalGet('cron/' . \Drupal::state()->get('system.cron_key'));
  }

}
