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
    $this->loadAllSchema();
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinition().
   */
  public function getDefinition($base_plugin_id) {
    if (isset($this->definitions[$base_plugin_id])) {
      $type = $base_plugin_id;
    }
    elseif (strpos($base_plugin_id, '.') && $name = $this->getFallbackName($base_plugin_id)) {
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
  protected function loadAllSchema() {
    foreach ($this->storage->listAll() as $name) {
      if ($schema = $this->storage->read($name)) {
        foreach ($schema as $type => $definition) {
          $this->definitions[$type] = $definition;
        }
      }
    }
  }

  /**
   * Gets fallback metadata name.
   *
   * @param string $name
   *   Configuration name or key.
   *
   * @return null|string
   *   Same name with the last part(s) replaced by the filesystem marker.
   *   for example, breakpoint.breakpoint.module.toolbar.narrow check for
   *   definition in below order:
   *     breakpoint.breakpoint.module.toolbar.*
   *     breakpoint.breakpoint.module.*.*
   *     breakpoint.breakpoint.*.*.*
   *     breakpoint.*.*.*.*
   *   Returns null, if no matching element.
   */
  protected function getFallbackName($name) {
    // Check for definition of $name with filesystem marker.
    $replaced = preg_replace('/(\.[^\.]+)([\.\*]*)$/', '.*\2', $name);
    if ($replaced != $name ) {
      if (isset($this->definitions[$replaced])) {
        return $replaced;
      }
      else {
        // No definition for this level(for example, breakpoint.breakpoint.*),
        // check for next level (which is, breakpoint.*.*).
        return self::getFallbackName($replaced);
      }
    }
  }
}
