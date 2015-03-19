<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\Provider\Cookie.
 */

namespace Drupal\Core\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cookie based authentication provider.
 */
class Cookie implements AuthenticationProviderInterface {

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Constructs a new cookie authentication provider.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   */
  public function __construct(SessionConfigurationInterface $session_configuration) {
    $this->sessionConfiguration = $session_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->hasSession() && $this->sessionConfiguration->hasSession($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    if ($request->getSession()->start()) {
      // @todo Remove global in https://www.drupal.org/node/2228393
      global $_session_user;
      return $_session_user;
    }

    return NULL;
  }

}
