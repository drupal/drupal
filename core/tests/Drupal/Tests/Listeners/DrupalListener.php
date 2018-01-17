<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\Test;

if (class_exists('PHPUnit_Runner_Version') && version_compare(\PHPUnit_Runner_Version::id(), '6.0.0', '<')) {
  class_alias('Drupal\Tests\Listeners\Legacy\DrupalListener', 'Drupal\Tests\Listeners\DrupalListener');
  // Using an early return instead of a else does not work when using the
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
  class DrupalListener extends BaseTestListener {
    use DeprecationListenerTrait;
    use DrupalComponentTestListenerTrait;
    use DrupalStandardsListenerTrait;

    /**
     * {@inheritdoc}
     */
    public function startTest(Test $test) {
      $this->deprecationStartTest($test);
    }

    /**
     * {@inheritdoc}
     */
    public function endTest(Test $test, $time) {
      $this->deprecationEndTest($test, $time);
      $this->componentEndTest($test, $time);
      $this->standardsEndTest($test, $time);
    }

  }
}
