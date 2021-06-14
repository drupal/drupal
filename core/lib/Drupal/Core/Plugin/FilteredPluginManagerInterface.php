<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for plugin managers that allow filtering definitions.
 */
interface FilteredPluginManagerInterface extends PluginManagerInterface {

  /**
   * Gets the plugin definitions for a given type and consumer and filters them.
   *
   * This allows modules and themes to alter plugin definitions at runtime,
   * which is useful for tasks like hiding specific plugins from a particular
   * user interface.
   *
   * @param string $consumer
   *   A string identifying the consumer of these plugin definitions.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[]|null $contexts
   *   (optional) Either an array of contexts to use for filtering, or NULL to
   *   not filter by contexts.
   * @param mixed[] $extra
   *   (optional) An associative array containing additional information
   *   provided by the code requesting the filtered definitions.
   *
   * @return \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[]|array[]
   *   An array of plugin definitions that are filtered.
   *
   * @see hook_plugin_filter_TYPE_alter()
   * @see hook_plugin_filter_TYPE__CONSUMER_alter()
   */
  public function getFilteredDefinitions($consumer, $contexts = NULL, array $extra = []);

}
