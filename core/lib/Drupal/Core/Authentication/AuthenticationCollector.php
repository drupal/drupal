<?php

namespace Drupal\Core\Authentication;

/**
 * A collector class for authentication providers.
 */
class AuthenticationCollector implements AuthenticationCollectorInterface {

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
  protected $providerOrders = [];

  /**
   * Sorted list of registered providers.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   */
  protected $sortedProviders;

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
    // Force the providers to be re-sorted.
    $this->sortedProviders = NULL;

    if ($global) {
      $this->globalProviders[$provider_id] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isGlobal($provider_id) {
    return isset($this->globalProviders[$provider_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider($provider_id) {
    return $this->providers[$provider_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedProviders() {
    if (!isset($this->sortedProviders)) {
      // Sort the providers according to priority.
      krsort($this->providerOrders);

      // Merge nested providers from $this->providers into $this->sortedProviders.
      $this->sortedProviders = array_merge(...$this->providerOrders);
    }

    return $this->sortedProviders;
  }

}
