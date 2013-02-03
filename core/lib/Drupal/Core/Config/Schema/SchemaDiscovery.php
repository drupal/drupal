<?php

/**
 * @file
 * Contains \Drupal\Config\Schema\SchemaDiscovery.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * A discovery mechanism that reads plugin definitions from schema data
 * in YAML format.
 */
class SchemaDiscovery implements DiscoveryInterface {

  /**
   * A storage controller instance for reading configuration schema data.
   *
   * @var Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The array of plugin definitions, keyed by plugin id.
   *
   * @var array
   */
  protected $definitions = array();

  /**
   * Public constructor.
   *
   * @param Drupal\Core\Config\StorageInterface $storage
   *   The storage controller object to use for reading schema data
   */
  public function __construct($storage) {
    $this->storage = $storage;
    // Load definitions for all enabled modules.
    foreach (module_list() as $module) {
      $this->loadSchema($module);
    }
    // @todo Load definitions for all enabled themes.

  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinition().
   */
  public function getDefinition($base_plugin_id) {
    if (isset($this->definitions[$base_plugin_id])) {
      $type = $base_plugin_id;
    }
    elseif (strpos($base_plugin_id, '.') && ($name = $this->getFallbackName($base_plugin_id)) && isset($this->definitions[$name])) {
      // Found a generic name, replacing the last element by '*'.
      $type = $name;
    }
    else {
      // If we don't have definition, return the 'default' element.
      // This should map to 'undefined' type by default, unless overridden.
      $type = 'default';
    }
    $definition = $this->definitions[$type];
    // Check whether this type is an extension of another one and compile it.
    if (isset($definition['type'])) {
      $merge = $this->getDefinition($definition['type']);
      $definition = NestedArray::mergeDeep($merge, $definition);
      // Unset type so we try the merge only once per type.
      unset($definition['type']);
      $this->definitions[$type] = $definition;
    }
    return $definition;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    return $this->definitions;
  }

  /**
   * Load schema for module / theme.
   */
  protected function loadSchema($component) {
    if ($schema = $this->storage->read($component . '.schema')) {
      foreach ($schema as $type => $definition) {
        $this->definitions[$type] = $definition;

      }
    }
  }

  /**
   * Gets fallback metadata name.
   *
   * @param string $name
   *   Configuration name or key.
   *
   * @return string
   *   Same name with the last part replaced by the filesystem marker.
   */
  protected static function getFallbackName($name) {
    $replaced = preg_replace('/\.[^.]+$/', '.' . '*', $name);
    if ($replaced != $name) {
      return $replaced;
    }
  }
}
