<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\Provider\Cookie.
 */

namespace Drupal\Core\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Cookie based authentication provider.
 */
class Cookie implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->hasSession();
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Global $user is deprecated, but the session system is still based on it.
    global $user;

    if ($request->getSession()->start()) {
      return $user;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    return FALSE;
  }
}
