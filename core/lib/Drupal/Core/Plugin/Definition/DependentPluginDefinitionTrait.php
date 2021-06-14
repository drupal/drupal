<?php

namespace Drupal\Core\Plugin\Definition;

/**
 * Provides a trait for a plugin definition that has dependencies.
 */
trait DependentPluginDefinitionTrait {

  /**
   * The dependencies of this plugin definition.
   *
   * @var array
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  protected $config_dependencies = [];

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencies() {
    return $this->config_dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigDependencies(array $config_dependencies) {
    $this->config_dependencies = $config_dependencies;
    return $this;
  }

}
