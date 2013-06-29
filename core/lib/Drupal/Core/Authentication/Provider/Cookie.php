<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\Provider\Cookie.
 */

namespace Drupal\Core\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Cookie based authentication provider.
 */
class Cookie implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Global $user is deprecated, but the session system is still based on it.
    global $user;
    require_once DRUPAL_ROOT . '/' . settings()->get('session_inc', 'core/includes/session.inc');
    drupal_session_initialize();
    if (drupal_session_started()) {
      return $user;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {
    drupal_session_commit();
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    return FALSE;
  }
}
