<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\AuthenticationManager.
 */

namespace Drupal\Core\Authentication;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AnonymousUserSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Manager for authentication.
 *
 * On each request, let all authentication providers try to authenticate the
 * user. The providers are iterated according to their priority and the first
 * provider detecting credentials for its method will become the triggered
 * provider. No further provider will get triggered.
 *
 * If no provider was triggered the lowest-priority provider is assumed to
 * be responsible. If no provider set an active user then the user is set to
 * anonymous.
 */
class AuthenticationManager implements AuthenticationProviderInterface, AuthenticationManagerInterface {

  /**
   * Array of all registered authentication providers, keyed by ID.
   *
   * @var array
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
   * @var array
   */
  protected $sortedProviders;

  /**
   * Id of the provider that authenticated the user.
   *
   * @var string
   */
  protected $triggeredProviderId = '';

  /**
   * Adds a provider to the array of registered providers.
   *
   * @param \Drupal\Core\Authentication\AuthenticationProviderInterface $provider
   *   The provider object.
   * @param string $id
   *   Identifier of the provider.
   * @param int $priority
   *   The providers priority.
   */
  public function addProvider(AuthenticationProviderInterface $provider, $id, $priority = 0) {
    // Remove the 'authentication.' prefix from the provider ID.
    $id = substr($id, 15);

    $this->providers[$id] = $provider;
    $this->providerOrders[$priority][$id] = $provider;
    // Force the builders to be re-sorted.
    $this->sortedProviders = NULL;
  }

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
    global $user;

    $account = NULL;

    // Iterate the allowed providers.
    foreach ($this->filterProviders($this->getSortedProviders(), $request) as $provider_id => $provider) {
      if ($provider->applies($request)) {
        // Try to authenticate with this provider, skipping all others.
        $account = $provider->authenticate($request);
        $this->triggeredProviderId = $provider_id;
        break;
      }
    }

    // No provider returned a valid account, so set the user to anonymous.
    if (!$account) {
      $account = new AnonymousUserSession();
    }

    // No provider was fired, so assume the one with the least priority
    // should have.
    if (!$this->triggeredProviderId) {
      $this->triggeredProviderId = $this->defaultProviderId();
    }

    // Save the authenticated account and the provider that supplied it
    //  for later access.
    $request->attributes->set('_authentication_provider', $this->triggeredProviderId);

    // The global $user object is included for backward compatibility only and
    // should be considered deprecated.
    // @todo Remove this line once global $user is no longer used.
    $user = $account;

    return $account;
  }

  /**
   * Returns the default provider ID.
   *
   * The default provider is the one with the lowest registered priority.
   *
   * @return string
   *   The ID of the default provider.
   */
  public function defaultProviderId() {
    $providers = $this->getSortedProviders();
    $provider_ids = array_keys($providers);
    return end($provider_ids);
  }

  /**
   * Returns the sorted array of authentication providers.
   *
   * @return array
   *   An array of authentication provider objects.
   */
  public function getSortedProviders() {
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

  /**
   * Filters a list of providers and only return those allowed on the request.
   *
   * @param \Drupal\Core\Authentication\AuthenticationProviderInterface[] $providers
   *   An array of authentication provider objects.
   * @param Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   *   The filtered array authentication provider objects.
   */
  protected function filterProviders(array $providers, Request $request) {
    $route = RouteMatch::createFromRequest($request)->getRouteObject();
    $allowed_providers = array();
    if ($route && $route->hasOption('_auth')) {
      $allowed_providers = $route->getOption('_auth');
    }
    elseif ($default_provider = $this->defaultProviderId()) {
      // @todo Mirrors the defective behavior of AuthenticationEnhancer and
      // restricts the list of allowed providers to the default provider if no
      // _auth was specified on the current route.
      //
      // This restriction will be removed by https://www.drupal.org/node/2286971
      // See also https://www.drupal.org/node/2283637
      $allowed_providers = array($default_provider);
    }

    return array_intersect_key($providers, array_flip($allowed_providers));
  }

  /**
   * Cleans up the authentication.
   *
   * Allow the triggered provider to clean up before the response is sent, e.g.
   * trigger a session commit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @see \Drupal\Core\Authentication\Provider\Cookie::cleanup()
   */
  public function cleanup(Request $request) {
    if (empty($this->providers[$this->triggeredProviderId])) {
      return;
    }
    $this->providers[$this->triggeredProviderId]->cleanup($request);
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    foreach ($this->filterProviders($this->getSortedProviders(), $event->getRequest()) as $provider) {
      if ($provider->handleException($event) === TRUE) {
        break;
      }
    }
  }

}
