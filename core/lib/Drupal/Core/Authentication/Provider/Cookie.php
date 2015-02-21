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
    if ($request->getSession()->start()) {
      // @todo Remove global in https://www.drupal.org/node/2286971
      global $_session_user;
      return $_session_user;
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
