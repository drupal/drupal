<?php

/**
 * @file
 * Contains Drupal\system\Tests\Routing\MockController.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Dummy class, just for testing.
 */
class MockController extends ContainerAware {

  /**
   * Does nothing; this is just a fake controller method.
   */
  public function run() {}

}
