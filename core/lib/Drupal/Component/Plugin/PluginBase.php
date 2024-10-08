<?php

namespace Drupal\Component\Plugin;

/**
 * Base class for plugins wishing to support metadata inspection.
 */
abstract class PluginBase implements PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * A string which is used to separate base plugin IDs from the derivative ID.
   */
  const DERIVATIVE_SEPARATOR = ':';

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin implementation definition.
   *
   * @var \Drupal\Component\Plugin\Definition\PluginDefinitionInterface|array
   */
  protected $pluginDefinition;

  /**
   * Configuration information passed into the plugin.
   *
   * When using an interface like
   * \Drupal\Component\Plugin\ConfigurableInterface, this is where the
   * configuration should be stored.
   *
   * Plugin configuration is optional, so plugin implementations must provide
   * their own setters and getters.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseId() {
    $plugin_id = $this->getPluginId();
    if (strpos($plugin_id, static::DERIVATIVE_SEPARATOR)) {
      [$plugin_id] = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 2);
    }
    return $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeId() {
    $plugin_id = $this->getPluginId();
    $derivative_id = NULL;
    if (strpos($plugin_id, static::DERIVATIVE_SEPARATOR)) {
      [, $derivative_id] = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 2);
    }
    return $derivative_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->pluginDefinition;
  }

  /**
   * Determines if the plugin is configurable.
   *
   * @return bool
   *   A boolean indicating whether the plugin is configurable.
   *
   * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use
   * instanceof to check if the plugin implements
   * \Drupal\Component\Plugin\ConfigurableInterface instead.
   *
   * @see https://www.drupal.org/node/3198285
   */
  public function isConfigurable() {
    @trigger_error(__CLASS__ . "::" . __FUNCTION__ . " is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use instanceof to check if the plugin implements \Drupal\Component\Plugin\ConfigurableInterface instead. See https://www.drupal.org/node/3198285", E_USER_DEPRECATED);
    return $this instanceof ConfigurableInterface;
  }

}
