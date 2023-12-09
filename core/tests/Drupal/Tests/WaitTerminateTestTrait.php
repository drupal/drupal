<?php

declare(strict_types=1);

namespace Drupal\Tests;

/**
 * Provides a method to enforce that requests will wait for the terminate event.
 */
trait WaitTerminateTestTrait {

  /**
   * Specify that subsequent requests must wait for the terminate event.
   *
   * The terminate event is fired after a response is sent to the user agent.
   * Tests with assertions which operate on data computed during the terminate
   * event need to enable this.
   */
  protected function setWaitForTerminate() {
    $this->container->get('state')->set('drupal.test_wait_terminate', TRUE);
  }

}
