<?php

namespace Drupal\rest_test\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Global authentication provider for testing purposes.
 */
class TestAuthGlobal implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->headers->has('REST-test-auth-global');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    return NULL;
  }

}
