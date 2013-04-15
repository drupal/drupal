<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Discovery\ViewsHandlerDiscovery.
 */

namespace Drupal\views\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\AnnotatedClassDiscovery;

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
   * @param array $root_namespaces
   *   (optional) Array of root paths keyed by the corresponding namespace to
   *   look for plugin implementations, \Plugin\views\$type will be appended to
   *   each namespace. Defaults to an empty array.
   */
  function __construct($type, array $root_namespaces = array()) {
    $this->type = $type;
    $annotation_namespaces = array(
      'Drupal\Component\Annotation' => DRUPAL_ROOT . '/core/lib',
    );
    $plugin_namespaces = array();
    foreach ($root_namespaces as $namespace => $dir) {
      $plugin_namespaces["$namespace\\Plugin\\views\\{$type}"] = array($dir);
    }
    parent::__construct($plugin_namespaces, $annotation_namespaces, 'Drupal\Component\Annotation\PluginID');
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
