<?php

namespace Drupal\Component\Plugin\Factory;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Default plugin factory.
 *
 * Instantiates plugin instances by passing the full configuration array as a
 * single constructor argument. Plugin types wanting to support plugin classes
 * with more flexible constructor signatures can do so by using an alternate
 * factory such as Drupal\Component\Plugin\Factory\ReflectionFactory.
 */
class DefaultFactory implements FactoryInterface {

  /**
   * The object that gets the plugin definitions that this factory instantiates.
   *
   * The plugin definition includes the plugin class and possibly other
   * information necessary for proper instantiation.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * Defines an interface each plugin should implement.
   *
   * @var string|null
   */
  protected $interface;

  /**
   * Constructs a Drupal\Component\Plugin\Factory\DefaultFactory object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The plugin discovery.
   * @param string|null $plugin_interface
   *   (optional) The interface each plugin should implement.
   */
  public function __construct(DiscoveryInterface $discovery, $plugin_interface = NULL) {
    $this->discovery = $discovery;
    $this->interface = $plugin_interface;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);
    return new $plugin_class($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Finds the class relevant for a given plugin.
   *
   * @param string $plugin_id
   *   The id of a plugin.
   * @param \Drupal\Component\Plugin\Definition\PluginDefinitionInterface|mixed[] $plugin_definition
   *   The plugin definition associated with the plugin ID.
   * @param string $required_interface
   *   (optional) The required plugin interface.
   *
   * @return string
   *   The appropriate class name.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when there is no class specified, the class doesn't exist, or
   *   the class does not implement the specified required interface.
   */
  public static function getPluginClass($plugin_id, $plugin_definition = NULL, $required_interface = NULL) {
    $missing_class_message = sprintf('The plugin (%s) did not specify an instance class.', $plugin_id);
    if (is_array($plugin_definition)) {
      if (empty($plugin_definition['class'])) {
        throw new PluginException($missing_class_message);
      }

      $class = $plugin_definition['class'];
    }
    elseif ($plugin_definition instanceof PluginDefinitionInterface) {
      if (!$plugin_definition->getClass()) {
        throw new PluginException($missing_class_message);
      }

      $class = $plugin_definition->getClass();
    }
    else {
      $plugin_definition_type = is_object($plugin_definition) ? get_class($plugin_definition) : gettype($plugin_definition);
      throw new PluginException(sprintf('%s can only handle plugin definitions that are arrays or that implement %s, but %s given.', __CLASS__, PluginDefinitionInterface::class, $plugin_definition_type));
    }

    if (!class_exists($class)) {
      throw new PluginException(sprintf('Plugin (%s) instance class "%s" does not exist.', $plugin_id, $class));
    }

    if ($required_interface && !is_subclass_of($class, $required_interface)) {
      throw new PluginException(sprintf('Plugin "%s" (%s) must implement interface %s.', $plugin_id, $class, $required_interface));
    }

    return $class;
  }

}
