<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\AuthenticationManager.
 */

namespace Drupal\Core\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
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
   * @param string $provider_id
   *   Identifier of the provider.
   * @param \Drupal\Core\Authentication\AuthenticationProviderInterface $provider
   *   The provider object.
   * @param int $priority
   *   The providers priority.
   */
  public function addProvider($provider_id, AuthenticationProviderInterface $provider, $priority = 0) {
    $provider_id = substr($provider_id, strlen('authentication.'));

    $this->providers[$provider_id] = $provider;
    $this->providerOrders[$priority][$provider_id] = $provider;
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

    // Iterate the availlable providers.
    foreach ($this->getSortedProviders() as $provider_id => $provider) {
      if ($provider->applies($request)) {
        // Try to authenticate with this provider, skipping all others.
        $account = $provider->authenticate($request);
        $this->triggeredProviderId = $provider_id;
        break;
      }
    }

    // No provider returned a valid account, so set the user to anonymous.
    if (!$account) {
      $account = drupal_anonymous_user();
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
    $request = $event->getRequest();

    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    $active_providers = ($route && $route->getOption('_auth')) ? $route->getOption('_auth') : array($this->defaultProviderId());

    // Get the sorted list of active providers for the given route.
    $providers = array_intersect($active_providers, array_keys($this->providers));

    foreach ($providers as $provider_id) {
      if ($this->providers[$provider_id]->handleException($event) == TRUE) {
        break;
      }
    }
  }
}
