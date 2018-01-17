<?php

namespace Drupal\Tests\Listeners\Legacy;

use Drupal\Tests\Listeners\DeprecationListenerTrait;
use Drupal\Tests\Listeners\DrupalComponentTestListenerTrait;
use Drupal\Tests\Listeners\DrupalStandardsListenerTrait;

/**
 * Listens to PHPUnit test runs.
 *
 * @internal
 *   This class is not public Drupal API.
 */
class DrupalListener extends \PHPUnit_Framework_BaseTestListener {
  use DeprecationListenerTrait;
  use DrupalComponentTestListenerTrait;
  use DrupalStandardsListenerTrait;

  /**
   * {@inheritdoc}
   */
  public function startTest(\PHPUnit_Framework_Test $test) {
    $this->deprecationStartTest($test);
  }

  /**
   * {@inheritdoc}
   */
  public function endTest(\PHPUnit_Framework_Test $test, $time) {
    $this->deprecationEndTest($test, $time);
    $this->componentEndTest($test, $time);
    $this->standardsEndTest($test, $time);
  }

}
