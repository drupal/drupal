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
class AuthenticationManager implements AuthenticationProviderInterface, AuthenticationProviderFilterInterface, AuthenticationProviderChallengeInterface, AuthenticationManagerInterface {

  /**
   * Array of all registered authentication providers, keyed by ID.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   */
  protected $providers;

  /**
   * Array of all providers and their priority.
   *
   * @var array
   */
  protected $providerOrders = array();

  /**
   * Sorted list of registered providers.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   */
  protected $sortedProviders;

  /**
   * List of providers which implement the filter interface.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderFilterInterface[]
   */
  protected $filters;

  /**
   * List of providers which implement the challenge interface.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderChallengeInterface[]
   */
  protected $challengers;

  /**
   * List of providers which are allowed on routes with no _auth option.
   *
   * @var string[]
   */
  protected $globalProviders;

  /**
   * {@inheritdoc}
   */
  public function addProvider(AuthenticationProviderInterface $provider, $provider_id, $priority = 0, $global = FALSE) {
    $this->providers[$provider_id] = $provider;
    $this->providerOrders[$priority][$provider_id] = $provider;
    // Force the builders to be re-sorted.
    $this->sortedProviders = NULL;

    if ($provider instanceof AuthenticationProviderFilterInterface) {
      $this->filters[$provider_id] = $provider;
    }
    if ($provider instanceof AuthenticationProviderChallengeInterface) {
      $this->challengers[$provider_id] = $provider;
    }

    if ($global) {
      $this->globalProviders[$provider_id] = TRUE;
    }
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
    return $this->providers[$provider_id]->authenticate($request);
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
      foreach ($this->getSortedProviders() as $provider_id => $provider) {
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
      return $this->challengers[$provider_id]->challengeException($request, $previous);
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
    foreach ($this->getSortedProviders() as $provider_id => $provider) {
      if ($provider->applies($request)) {
        return $provider_id;
      }
    }
  }

  /**
   * Returns the id of the challenge provider for a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return string|NULL
   *   The id of the first authentication provider which applies to the request.
   *   If no application detects appropriate credentials, then NULL is returned.
   */
  protected function getChallenger(Request $request) {
    if (!empty($this->challengers)) {
      foreach ($this->getSortedProviders($request, FALSE) as $provider_id => $provider) {
        if (isset($this->challengers[$provider_id]) && !$provider->applies($request) && $this->applyFilter($request, FALSE, $provider_id)) {
          return $provider_id;
        }
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
    if (isset($this->filters[$provider_id])) {
      $result = $this->filters[$provider_id]->appliesToRoutedRequest($request, $authenticated);
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
      return isset($this->globalProviders[$provider_id]);
    }
  }

  /**
   * Returns the sorted array of authentication providers.
   *
   * @todo Replace with a list of providers sorted during compile time in
   *   https://www.drupal.org/node/2432585.
   *
   * @return \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   *   An array of authentication provider objects.
   */
  protected function getSortedProviders() {
    if (!isset($this->sortedProviders)) {
      // Sort the builders according to priority.
      krsort($this->providerOrders);
      // Merge nested providers from $this->providers into $this->sortedProviders.
      $this->sortedProviders = array();
      foreach ($this->providerOrders as $providers) {
        $this->sortedProviders = array_merge($this->sortedProviders, $providers);
      }
    }
    return $this->sortedProviders;
  }

}
