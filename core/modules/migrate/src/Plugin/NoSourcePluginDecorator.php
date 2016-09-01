<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Remove definitions which refer to a non-existing source plugin.
 */
class NoSourcePluginDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Constructs a NoSourcePluginDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The object implementing DiscoveryInterface that is being decorated.
   */
  public function __construct(DiscoveryInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    /** @var \Drupal\Component\Plugin\PluginManagerInterface $source_plugin_manager */
    $source_plugin_manager = \Drupal::service('plugin.manager.migrate.source');
    return array_filter($this->decorated->getDefinitions(), function (array $definition) use ($source_plugin_manager) {
      return $source_plugin_manager->hasDefinition($definition['source']['plugin']);
    });
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
