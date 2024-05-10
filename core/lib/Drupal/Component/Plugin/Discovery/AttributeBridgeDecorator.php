<?php

namespace Drupal\Component\Plugin\Discovery;

/**
 * Ensures that all definitions are run through the attribute process.
 */
class AttributeBridgeDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * AttributeBridgeDecorator constructor.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The discovery object that is being decorated.
   * @param string $pluginDefinitionAttributeName
   *   The name of the attribute that contains the plugin definition. The class
   *   corresponding to this name must implement
   *   \Drupal\Component\Plugin\Attribute\AttributeInterface.
   */
  public function __construct(
    protected readonly DiscoveryInterface $decorated,
    protected readonly string $pluginDefinitionAttributeName,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();
    foreach ($definitions as $id => $definition) {
      // Attribute constructors expect an array of values. If the definition is
      // not an array, it usually means it has been processed already and can be
      // ignored.
      if (is_array($definition)) {
        $class = $definition['class'] ?? NULL;
        $provider = $definition['provider'] ?? NULL;
        unset($definition['class'], $definition['provider']);
        /** @var \Drupal\Component\Plugin\Attribute\AttributeInterface $attribute */
        $attribute = new $this->pluginDefinitionAttributeName(...$definition);
        if (isset($class)) {
          $attribute->setClass($class);
        }
        if (isset($provider)) {
          $attribute->setProvider($provider);
        }
        $definitions[$id] = $attribute->get();
      }
    }
    return $definitions;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   *
   * @param string $method
   *   The method to call on the decorated plugin discovery.
   * @param array $args
   *   The arguments to send to the method.
   *
   * @return mixed
   *   The method result.
   */
  public function __call($method, $args) {
    return $this->decorated->{$method}(...$args);
  }

}
