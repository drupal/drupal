<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\AuthenticationEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Drupal\Core\Authentication\AuthenticationManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Authentication cleanup for incoming routes.
 *
 * The authentication system happens before routing, so all authentication
 * providers will attempt to authorize a user. However, not all routes allow
 * all authentication mechanisms. Instead, we check if the used provider is
 * valid for the matched route and if not, force the user to anonymous.
 */
class AuthenticationEnhancer implements RouteEnhancerInterface {

  /**
   * The authentication manager.
   *
   * @var \Drupal\Core\Authentication\AuthenticationManager
   */
  protected $manager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a AuthenticationEnhancer object.
   *
   * @param \Drupal\Core\Authentication\AuthenticationManagerInterface $manager
   *   The authentication manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  function __construct(AuthenticationManagerInterface $manager, AccountProxyInterface $current_user) {
    $this->manager = $manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $auth_provider_triggered = $request->attributes->get('_authentication_provider');
    if (!empty($auth_provider_triggered)) {
      $route = isset($defaults[RouteObjectInterface::ROUTE_OBJECT]) ? $defaults[RouteObjectInterface::ROUTE_OBJECT] : NULL;

      $auth_providers = ($route && $route->getOption('_auth')) ? $route->getOption('_auth') : array($this->manager->defaultProviderId());
      // If the request was authenticated with a non-permitted provider,
      // force the user back to anonymous.
      if (!in_array($auth_provider_triggered, $auth_providers)) {
        $anonymous_user = new AnonymousUserSession();

        $this->currentUser->setAccount($anonymous_user);

        // The global $user object is included for backward compatibility only
        // and should be considered deprecated.
        // @todo Remove this line once global $user is no longer used.
        $GLOBALS['user'] = $anonymous_user;
      }
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return TRUE;
  }

}
