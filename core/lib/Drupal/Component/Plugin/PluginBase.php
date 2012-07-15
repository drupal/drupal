<?php
/**
 * @file
 * Definition of Drupal\Component\Plugin\PluginBase
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Base class for plugins wishing to support metadata inspection.
 */
abstract class PluginBase implements PluginInspectionInterface {

  /**
   * The discovery object.
   *
   * @var Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The plugin_id.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * Configuration information passed into the plugin.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param DiscoveryInterface $discovery
   *   The Discovery class that holds access to the plugin implementation
   *   definition.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    $this->configuration = $configuration;
    $this->plugin_id = $plugin_id;
    $this->discovery = $discovery;
  }

  /**
   * Implements Drupal\Component\Plugin\PluginInterface::getPluginId().
   */
  public function getPluginId() {
    return $this->plugin_id;
  }

  /**
   * Implements Drupal\Component\Plugin\PluginInterface::getDefinition().
   */
  public function getDefinition() {
    return $this->discovery->getDefinition($this->plugin_id);
  }

  // Note: Plugin configuration is optional so its left to the plugin type to
  // require a getter as part of its interface.
}
