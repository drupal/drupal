<?php

/**
 * @file
 * Contains \Drupal\tour\TipsBag.
 */

namespace Drupal\tour;

use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\NestedArray;

/**
 * A collection of tips.
 */
class TipsBag extends PluginBag {

  /**
   * The initial configuration for each tip in the bag.
   *
   * @var array
   *   An associative array containing the initial configuration for each tip
   *   in the bag, keyed by plugin instance ID.
   */
  protected $configurations = array();

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a TipsBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each tour in the bag, keyed by plugin instance ID.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations = array()) {
    $this->manager = $manager;
    $this->configurations = $configurations;

    if (!empty($configurations)) {
      $this->instanceIDs = array_combine(array_keys($configurations), array_keys($configurations));
    }
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginBag::initializePlugin().
   */
  protected function initializePlugin($instance_id) {
    // If the tip was initialized before, just return.
    if (isset($this->pluginInstances[$instance_id])) {
      return;
    }

    $type = $this->configurations[$instance_id]['plugin'];
    $definition = $this->manager->getDefinition($type);

    if (isset($definition)) {
      $this->addInstanceID($instance_id);
      $configuration = $definition;

      // Merge the actual configuration into the default configuration.
      if (isset($this->configurations[$instance_id])) {
        $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$instance_id]);
      }
      $this->pluginInstances[$instance_id] = $this->manager->createInstance($type, $configuration);
    }
    else {
      throw new PluginException(format_string("Unknown tip plugin ID '@tip'.", array('@tip' => $instance_id)));
    }
  }

}
