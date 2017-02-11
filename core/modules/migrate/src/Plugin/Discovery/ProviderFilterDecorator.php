<?php

namespace Drupal\migrate\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Remove plugin definitions with non-existing providers.
 *
 * @internal
 *   This is a temporary solution to the fact that migration source plugins have
 *   more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
 */
class ProviderFilterDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * A callable for testing if a provider exists.
   *
   * @var callable
   */
  protected $providerExists;

  /**
   * Constructs a InheritProviderDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The object implementing DiscoveryInterface that is being decorated.
   * @param callable $provider_exists
   *   A callable, gets passed a provider name, should return TRUE if the
   *   provider exists and FALSE if not.
   */
  public function __construct(DiscoveryInterface $decorated, callable $provider_exists) {
    $this->decorated = $decorated;
    $this->providerExists = $provider_exists;
  }

  /**
   * Removes plugin definitions with non-existing providers.
   *
   * @param mixed[] $definitions
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   * @param callable $provider_exists
   *   A callable, gets passed a provider name, should return TRUE if the
   *   provider exists and FALSE if not.
   *
   * @return array|\mixed[]
   *   An array of plugin definitions. If a definition is an array and has a
   *   provider key that provider is guaranteed to exist.
   */
  public static function filterDefinitions(array $definitions, callable $provider_exists) {
    // Besides what the caller accepts, we also accept core or component.
    $provider_exists = function ($provider) use ($provider_exists) {
      return in_array($provider, ['core', 'component']) || $provider_exists($provider);
    };
    return array_filter($definitions, function ($definition) use ($provider_exists) {
      // Plugin definitions can be objects (for example, Typed Data) those will
      // become empty array here and cause no problems.
      $definition = (array) $definition + ['provider' => []];
      // There can be one or many providers, handle them as multiple always.
      $providers = (array) $definition['provider'];
      return count($providers) == count(array_filter($providers, $provider_exists));
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return static::filterDefinitions($this->decorated->getDefinitions(), $this->providerExists);
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   *
   * @param string $method
   *   The method to call on the decorated object.
   * @param array $args
   *   Call arguments.
   *
   * @return mixed
   *   The return value from the method on the decorated object.
   */
  public function __call($method, array $args) {
    return call_user_func_array([$this->decorated, $method], $args);
  }

}
