<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\DefaultPluginBag.
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Exception\UnknownPluginException;
use Drupal\Component\Utility\MapArray;

/**
 * Provides a default plugin bag for a plugin type.
 *
 * A plugin bag is used to contain plugins that will be lazily instantiated. The
 * configurations of each potential plugin are passed in, and the configuration
 * key containing the plugin ID is specified by self::$pluginKey.
 */
class DefaultPluginBag extends PluginBag {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The initial configuration for each plugin in the bag.
   *
   * @var array
   *   An associative array containing the initial configuration for each plugin
   *   in the bag, keyed by plugin instance ID.
   */
  protected $configurations = array();

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'id';

  /**
   * The original order of the instances.
   *
   * @var array
   */
  protected $originalOrder = array();

  /**
   * Constructs a new DefaultPluginBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each plugin in the bag, keyed by plugin instance ID.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations = array()) {
    $this->manager = $manager;
    $this->configurations = $configurations;

    if (!empty($configurations)) {
      $this->instanceIDs = MapArray::copyValuesToKeys(array_keys($configurations));
      // Store the original order of the instance IDs for export.
      $this->originalOrder = $this->instanceIDs;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = isset($this->configurations[$instance_id]) ? $this->configurations[$instance_id] : array();
    if (!isset($configuration[$this->pluginKey])) {
      throw new UnknownPluginException($instance_id);
    }
    $this->pluginInstances[$instance_id] = $this->manager->createInstance($configuration[$this->pluginKey], $configuration);
    $this->addInstanceID($instance_id);
  }

  /**
   * Sorts all plugin instances in this bag.
   *
   * @return self
   *   Returns the plugin bag.
   */
  public function sort() {
    uasort($this->instanceIDs, array($this, 'sortHelper'));
    return $this;
  }

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);
    return strnatcasecmp($a->getPluginId(), $b->getPluginId());
  }

  /**
   * Returns the current configuration of all plugins in this bag.
   *
   * @return array
   *   An associative array keyed by instance ID, whose values are up-to-date
   *   plugin configurations.
   */
  public function getConfiguration() {
    $instances = array();
    $this->rewind();
    // Store the current order of the instances.
    $current_order = $this->instanceIDs;
    // Reorder the instances to match the original order, adding new instances
    // to the end.
    $this->instanceIDs = $this->originalOrder + $current_order;

    foreach ($this as $instance_id => $instance) {
      if ($instance instanceof ConfigurablePluginInterface) {
        $instances[$instance_id] = $instance->getConfiguration();
      }
      else {
        $instances[$instance_id] = $this->configurations[$instance_id];
      }
    }
    // Restore the current order.
    $this->instanceIDs = $current_order;
    return $instances;
  }

  /**
   * Updates the configuration for a plugin instance.
   *
   * If there is no plugin instance yet, a new will be instantiated. Otherwise,
   * the existing instance is updated with the new configuration.
   *
   * @param string $instance_id
   *   The ID of a plugin to set the configuration for.
   * @param array $configuration
   *   The plugin configuration to set.
   */
  public function setConfiguration($instance_id, array $configuration) {
    $this->configurations[$instance_id] = $configuration;
    $instance = $this->get($instance_id);
    if ($instance instanceof ConfigurablePluginInterface) {
      $instance->setConfiguration($configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeInstanceID($instance_id) {
    parent::removeInstanceID($instance_id);
    unset($this->originalOrder[$instance_id]);
    unset($this->configurations[$instance_id]);
  }

}
