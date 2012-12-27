<?php

/**
 * @file
 * Definition of Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\AnnotatedClassDiscovery as ComponentAnnotatedClassDiscovery;

/**
 * Defines a discovery mechanism to find annotated plugins in PSR-0 namespaces.
 */
class AnnotatedClassDiscovery extends ComponentAnnotatedClassDiscovery {

  /**
   * Constructs an AnnotatedClassDiscovery object.
   */
  function __construct($owner, $type, $root_namespaces = NULL) {
    $this->owner = $owner;
    $this->type = $type;
    $this->rootNamespaces = $root_namespaces;
    $annotation_namespaces = array(
      'Drupal\Component\Annotation' => DRUPAL_ROOT . '/core/lib',
      'Drupal\Core\Annotation' => DRUPAL_ROOT . '/core/lib',
    );
    parent::__construct(array(), $annotation_namespaces, 'Drupal\Core\Annotation\Plugin');
  }

  /**
   * Overrides Drupal\Component\Plugin\Discovery\AnnotatedClassDiscovery::getPluginNamespaces().
   *
   * This is overridden rather than set in the constructor, because Drupal
   * modules can be enabled (and therefore, namespaces registered) during the
   * lifetime of a plugin manager.
   */
  protected function getPluginNamespaces() {
    $plugin_namespaces = array();
    $root_namespaces = isset($this->rootNamespaces) ? $this->rootNamespaces : drupal_classloader()->getNamespaces();
    foreach ($root_namespaces as $namespace => $dirs) {
      $plugin_namespaces["$namespace\\Plugin\\{$this->owner}\\{$this->type}"] = $dirs;
    }
    return $plugin_namespaces;
  }

}
