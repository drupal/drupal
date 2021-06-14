<?php

namespace Drupal\views\Plugin;

/**
 * Provides an interface for a plugin that has dependencies that can be removed.
 *
 * @ingroup views_plugins
 */
interface DependentWithRemovalPluginInterface {

  /**
   * Allows a plugin to define whether it should be removed.
   *
   * If this method returns TRUE then the plugin should be removed.
   *
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return bool
   *   TRUE if the plugin instance should be removed.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   * @see \Drupal\Core\Config\ConfigEntityBase::preDelete()
   * @see \Drupal\Core\Config\ConfigManager::uninstall()
   * @see \Drupal\Core\Entity\EntityDisplayBase::onDependencyRemoval()
   */
  public function onDependencyRemoval(array $dependencies);

}
