<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Discovery\ViewsHandlerDiscovery.
 */

namespace Drupal\views\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Defines a discovery mechanism to find Views handlers in PSR-0 namespaces.
 */
class ViewsHandlerDiscovery extends AnnotatedClassDiscovery {

  /**
   * The type of handler being discovered.
   *
   * @var string
   */
  protected $type;

  /**
   * Constructs a ViewsHandlerDiscovery object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $root_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  function __construct($type, \Traversable $root_namespaces) {
    $this->type = $type;
    parent::__construct("Plugin/views/$type", $root_namespaces, 'Drupal\Component\Annotation\PluginID');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // Add the plugin_type to the definition.
    $definitions = parent::getDefinitions();
    foreach ($definitions as $key => $definition) {
      $definitions[$key]['plugin_type'] = $this->type;
    }
    return $definitions;
  }

}
