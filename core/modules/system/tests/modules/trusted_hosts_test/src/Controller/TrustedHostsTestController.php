<?php

namespace Drupal\trusted_hosts_test\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a test controller for testing the trusted hosts setting.
 */
class TrustedHostsTestController {

  /**
   * Creates a fake request and prints out its host.
   */
  public function fakeRequestHost() {
    $request = Request::create('/');
    return ['#markup' => 'Host: ' . $request->getHost()];
  }

  /**
   * Creates a fake request and prints out the class name of the specified bag.
   */
  public function bagType($bag) {
    $request = Request::create('/');
    return ['#markup' => 'Type: ' . get_class($request->$bag)];
  }

}
