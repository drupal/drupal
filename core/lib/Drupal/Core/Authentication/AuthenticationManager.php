<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\AuthenticationManager.
 */

namespace Drupal\Core\Authentication;

use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manager for authentication.
 *
 * On each request, let all authentication providers try to authenticate the
 * user. The providers are iterated according to their priority and the first
 * provider detecting credentials for its method wins. No further provider will
 * get triggered.
 *
 * If no provider set an active user then the user is set to anonymous.
 */
class AuthenticationManager implements AuthenticationProviderInterface, AuthenticationProviderFilterInterface, AuthenticationProviderChallengeInterface {

  /**
   * The authentication provider collector.
   *
   * @var \Drupal\Core\Authentication\AuthenticationCollectorInterface
   */
  protected $authCollector;

  /**
   * Creates a new authentication manager instance.
   *
   * @param \Drupal\Core\Authentication\AuthenticationCollectorInterface $auth_collector
   *   The authentication provider collector.
   */
  public function __construct(AuthenticationCollectorInterface $auth_collector) {
    $this->authCollector = $auth_collector;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return (bool) $this->getProvider($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $provider_id = $this->getProvider($request);
    $provider = $this->authCollector->getProvider($provider_id);

    if ($provider) {
      return $provider->authenticate($request);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesToRoutedRequest(Request $request, $authenticated) {
    $result = FALSE;

    if ($authenticated) {
      $result = $this->applyFilter($request, $authenticated, $this->getProvider($request));
    }
    else {
      foreach ($this->authCollector->getSortedProviders() as $provider_id => $provider) {
        if ($this->applyFilter($request, $authenticated, $provider_id)) {
          $result = TRUE;
          break;
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function challengeException(Request $request, \Exception $previous) {
    $provider_id = $this->getChallenger($request);

    if ($provider_id) {
      $provider = $this->authCollector->getProvider($provider_id);
      return $provider->challengeException($request, $previous);
    }
  }

  /**
   * Returns the id of the authentication provider for a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return string|NULL
   *   The id of the first authentication provider which applies to the request.
   *   If no application detects appropriate credentials, then NULL is returned.
   */
  protected function getProvider(Request $request) {
    foreach ($this->authCollector->getSortedProviders() as $provider_id => $provider) {
      if ($provider->applies($request)) {
        return $provider_id;
      }
    }
  }

  /**
   * Returns the ID of the challenge provider for a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return string|NULL
   *   The ID of the first authentication provider which applies to the request.
   *   If no application detects appropriate credentials, then NULL is returned.
   */
  protected function getChallenger(Request $request) {
    foreach ($this->authCollector->getSortedProviders() as $provider_id => $provider) {
      if (($provider instanceof AuthenticationProviderChallengeInterface) && !$provider->applies($request) && $this->applyFilter($request, FALSE, $provider_id)) {
        return $provider_id;
      }
    }
  }

  /**
   * Checks whether a provider is allowed on the given request.
   *
   * If no filter is registered for the given provider id, the default filter
   * is applied.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   * @param bool $authenticated
   *   Whether or not the request is authenticated.
   * @param string $provider_id
   *   The id of the authentication provider to check access for.
   *
   * @return bool
   *   TRUE if provider is allowed, FALSE otherwise.
   */
  protected function applyFilter(Request $request, $authenticated, $provider_id) {
    $provider = $this->authCollector->getProvider($provider_id);

    if ($provider && ($provider instanceof AuthenticationProviderFilterInterface)) {
      $result = $provider->appliesToRoutedRequest($request, $authenticated);
    }
    else {
      $result = $this->defaultFilter($request, $provider_id);
    }

    return $result;
  }

  /**
   * Default implementation of the provider filter.
   *
   * Checks whether a provider is allowed as per the _auth option on a route. If
   * the option is not set or if the request did not match any route, only
   * providers from the global provider set are allowed.
   *
   * If no filter is registered for the given provider id, the default filter
   * is applied.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   * @param string $provider_id
   *   The id of the authentication provider to check access for.
   *
   * @return bool
   *   TRUE if provider is allowed, FALSE otherwise.
   */
  protected function defaultFilter(Request $request, $provider_id) {
    $route = RouteMatch::createFromRequest($request)->getRouteObject();
    $has_auth_option = isset($route) && $route->hasOption('_auth');

    if ($has_auth_option) {
      return in_array($provider_id, $route->getOption('_auth'));
    }
    else {
      return $this->authCollector->isGlobal($provider_id);
    }
  }

}
