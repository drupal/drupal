<?php

namespace Drupal\media_test_oembed;

use Drupal\Core\Cache\NullBackend;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ProviderRepository as BaseProviderRepository;

/**
 * Overrides the oEmbed provider repository service for testing purposes.
 *
 * This service does not use caching at all, and will always try to retrieve
 * provider data from state before calling the parent methods.
 */
class ProviderRepository extends BaseProviderRepository {

  /**
   * {@inheritdoc}
   */
  public function __construct(...$arguments) {
    parent::__construct(...$arguments);
    $this->cacheBackend = new NullBackend('default');
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return \Drupal::state()->get(static::class) ?: parent::getAll();
  }

  /**
   * {@inheritdoc}
   */
  public function get($provider_name) {
    $providers = \Drupal::state()->get(static::class, []);

    if (isset($providers[$provider_name])) {
      return $providers[$provider_name];
    }
    return parent::get($provider_name);
  }

  /**
   * Stores an oEmbed provider value object in state.
   *
   * @param \Drupal\media\OEmbed\Provider $provider
   *   The provider to store.
   */
  public function setProvider(Provider $provider) {
    $providers = \Drupal::state()->get(static::class, []);
    $name = $provider->getName();
    $providers[$name] = $provider;
    \Drupal::state()->set(static::class, $providers);
  }

}
