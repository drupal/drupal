<?php

namespace Drupal\Core\Plugin\Definition;

/**
 * Provides an interface for a plugin definition that has dependencies.
 */
interface DependentPluginDefinitionInterface {

  /**
   * Gets the config dependencies of this plugin definition.
   *
   * @return array
   *   An array of config dependencies.
   *
   * @see \Drupal\Core\Plugin\PluginDependencyTrait::calculatePluginDependencies()
   */
  public function getConfigDependencies();

  /**
   * Sets the config dependencies of this plugin definition.
   *
   * @param array $config_dependencies
   *   An array of config dependencies.
   *
   * @return $this
   */
  public function setConfigDependencies(array $config_dependencies);

}
