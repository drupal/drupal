<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Provides an interface for plugins that react when dependencies are removed.
 *
 * @ingroup plugin_api
 */
interface RemovableDependentPluginInterface extends DependentPluginInterface {

  /**
   * Informs the plugin in a collection to act on removal of dependencies.
   *
   * This method allows a plugin instance in a collection to remove dependencies
   * from their configuration. For example, if a plugin integrates with a
   * specific module, it should remove that module from its own configuration
   * when the module is uninstalled.
   *
   * @param array<string, list<string>> $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return \Drupal\Core\Plugin\RemovableDependentPluginReturn
   *   - RemovableDependentPluginReturn::Changed if the configuration of the
   *     plugin instance has changed
   *   - RemovableDependentPluginReturn::Remove if the plugin instance should be
   *     removed from the plugin collection
   *   - RemovableDependentPluginReturn::Unchanged if the configuration of the
   *     plugin instance has not changed.
   */
  public function onCollectionDependencyRemoval(array $dependencies): RemovableDependentPluginReturn;

}
