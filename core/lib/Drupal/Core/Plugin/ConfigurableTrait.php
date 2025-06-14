<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin;

use Drupal\Component\Utility\NestedArray;

/**
 * Implementation class for \Drupal\Component\Plugin\ConfigurableInterface.
 *
 * In order for configurable plugins to maintain their configuration, the
 * default configuration must be merged into any explicitly defined
 * configuration. This trait provides the appropriate getters and setters to
 * handle this logic, removing the need for excess boilerplate.
 *
 * To use this trait implement ConfigurableInterface and add a constructor. In
 * the constructor call the parent constructor and then call setConfiguration().
 * That will merge the explicitly defined plugin configuration and the default
 * plugin configuration.
 *
 * @ingroup Plugin
 */
trait ConfigurableTrait {

  /**
   * Configuration information passed into the plugin.
   *
   * This property is declared in \Drupal\Component\Plugin\PluginBase as well,
   * which most classes using this trait will ultimately be extending. It is
   * re-declared here to make the trait self-contained and to permit use of the
   * trait in classes that do not extend PluginBase.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An associative array containing the plugin's configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurableInterface::getConfiguration()
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Sets the configuration for this plugin instance.
   *
   * The provided configuration is merged with the plugin's default
   * configuration. If the same configuration key exists in both configurations,
   * then the value in the provided configuration will override the default.
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration.
   *
   * @return $this
   *
   * @see \Drupal\Component\Plugin\ConfigurableInterface::setConfiguration()
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeepArray([$this->defaultConfiguration(), $configuration], TRUE);
    return $this;
  }

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array containing the default configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurableInterface::defaultConfiguration()
   */
  public function defaultConfiguration() {
    return [];
  }

}
