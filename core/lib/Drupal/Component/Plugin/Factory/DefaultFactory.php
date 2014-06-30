<?php
/**
 * @file
 * Definition of Drupal\Component\Plugin\Factory\DefaultFactory.
 */

namespace Drupal\Component\Plugin\Factory;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

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
   * The object that retrieves the definitions of the plugins that this factory instantiates.
   *
   * The plugin definition includes the plugin class and possibly other
   * information necessary for proper instantiation.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * Constructs a Drupal\Component\Plugin\Factory\DefaultFactory object.
   */
  public function __construct(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

  /**
   * Implements Drupal\Component\Plugin\Factory\FactoryInterface::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition);
    return new $plugin_class($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Finds the class relevant for a given plugin.
   *
   * @param string $plugin_id
   *   The id of a plugin.
   * @param mixed $plugin_definition
   *   The plugin definition associated with the plugin ID.
   *
   * @return string
   *   The appropriate class name.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function getPluginClass($plugin_id, $plugin_definition = NULL) {
    if (empty($plugin_definition['class'])) {
      throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $plugin_id));
    }

    $class = $plugin_definition['class'];

    if (!class_exists($class)) {
      throw new PluginException(sprintf('Plugin (%s) instance class "%s" does not exist.', $plugin_id, $class));
    }

    return $class;
  }
}
