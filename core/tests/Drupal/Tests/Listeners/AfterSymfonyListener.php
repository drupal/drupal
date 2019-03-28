<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

if (class_exists('PHPUnit_Runner_Version') && version_compare(\PHPUnit_Runner_Version::id(), '6.0.0', '<')) {
  class_alias('Drupal\Tests\Listeners\Legacy\AfterSymfonyListener', 'Drupal\Tests\Listeners\AfterSymfonyListener');
  // Using an early return instead of an else does not work when using the
  // PHPUnit phar due to some weird PHP behavior (the class gets defined without
  // executing the code before it and so the definition is not properly
  // conditional).
}
else {
  /**
   * Listens to PHPUnit test runs.
   *
   * @internal
   */
  class AfterSymfonyListener implements TestListener {
    use TestListenerDefaultImplementation;

    /**
     * {@inheritdoc}
     */
    public function endTest(Test $test, $time) {
      restore_error_handler();
    }

  }
}
