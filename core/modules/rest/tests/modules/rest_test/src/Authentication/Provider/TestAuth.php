<?php

declare(strict_types=1);

namespace Drupal\rest_test\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication provider for testing purposes.
 */
class TestAuth implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->headers->has('REST-test-auth');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    return NULL;
  }

}
