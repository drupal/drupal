<?php

namespace Drupal\Tests\Listeners\Legacy;

/**
 * Listens to PHPUnit test runs.
 *
 * @internal
 */
class AfterSymfonyListener extends \PHPUnit_Framework_BaseTestListener {

  /**
   * {@inheritdoc}
   */
  public function endTest(\PHPUnit_Framework_Test $test, $time) {
    restore_error_handler();
  }

}
