<?php

/**
 * @file
 * Contains Drupal\trusted_hosts_test\Controller\TrustedHostsTestController.
 */

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

}

