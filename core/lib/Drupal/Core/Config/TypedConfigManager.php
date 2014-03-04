<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigManager.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Manages config type plugins.
 */
class TypedConfigManager extends PluginManagerBase implements TypedConfigManagerInterface {

  /**
   * The cache ID for the definitions.
   *
   * @var string
   */
  const CACHE_ID = 'typed_config_definitions';

  /**
   * A storage controller instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * A storage controller instance for reading configuration schema data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $schemaStorage;

  /**
   * The array of plugin definitions, keyed by plugin id.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Cache backend for the definitions.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The storage controller object to use for reading schema data
   * @param \Drupal\Core\Config\StorageInterface $schemaStorage
   *   The storage controller object to use for reading schema data
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use for caching the definitions.
   */
  public function __construct(StorageInterface $configStorage, StorageInterface $schemaStorage, CacheBackendInterface $cache) {
    $this->configStorage = $configStorage;
    $this->schemaStorage = $schemaStorage;
    $this->cache = $cache;
  }

  /**
   * Gets typed configuration data.
   *
   * @param string $name
   *   Configuration object name.
   *
   * @return \Drupal\Core\Config\Schema\Element
   *   Typed configuration element.
   */
  public function get($name) {
    $data = $this->configStorage->read($name);
    $definition = $this->getDefinition($name);
    return $this->create($definition, $data);
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedDataManager::create()
   *
   * Fills in default type and does variable replacement.
   */
  public function create(array $definition, $value = NULL, $name = NULL, $parent = NULL) {
    if (!isset($definition['type'])) {
      // Set default type 'string' if possible. If not it will be 'undefined'.
      if (is_string($value)) {
        $definition['type'] = 'string';
      }
      else {
        $definition['type'] = 'undefined';
      }
    }
    elseif (strpos($definition['type'], ']')) {
      // Replace variable names in definition.
      $replace = is_array($value) ? $value : array();
      if (isset($parent)) {
        $replace['%parent'] = $parent;
      }
      if (isset($name)) {
        $replace['%key'] = $name;
      }
      $definition['type'] = $this->replaceName($definition['type'], $replace);
    }
    // Create typed config object.
    $wrapper = $this->createInstance($definition['type'], $definition, $name, $parent);
    if (isset($value)) {
      $wrapper->setValue($value, FALSE);
    }
    return $wrapper;
  }

  /**
   * Overrides Drupal\Core\TypedData\TypedDataFactory::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array(), $name = NULL, $parent = NULL) {
    $type_definition = $this->getDefinition($plugin_id);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(String::format('Invalid data type %plugin_id has been given.', array('%plugin_id' => $plugin_id)));
    }

    $configuration += $type_definition;
    // Allow per-data definition overrides of the used classes, i.e. take over
    // classes specified in the data definition.
    $key = empty($configuration['list']) ? 'class' : 'list class';
    if (isset($configuration[$key])) {
      $class = $configuration[$key];
    }
    elseif (isset($type_definition[$key])) {
      $class = $type_definition[$key];
    }

    if (!isset($class)) {
      throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $plugin_id));
    }
    return new $class($configuration, $name, $parent);
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinition().
   */
  public function getDefinition($base_plugin_id) {
    $definitions = $this->getDefinitions();
    if (isset($definitions[$base_plugin_id])) {
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
    $definition = $definitions[$type];
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
   * {@inheritdoc}
   */
  public function getDefinitions() {
    if (!isset($this->definitions)) {
      if ($cache = $this->cache->get($this::CACHE_ID)) {
        $this->definitions = $cache->data;
      }
      else {
        $this->definitions = array();
        foreach ($this->schemaStorage->readMultiple($this->schemaStorage->listAll()) as $schema) {
          foreach ($schema as $type => $definition) {
            $this->definitions[$type] = $definition;
          }
        }
        $this->cache->set($this::CACHE_ID, $this->definitions);
      }
    }
    return $this->definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->definitions = NULL;
    $this->schemaStorage->reset();
    $this->cache->delete($this::CACHE_ID);
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
        return $this->getFallbackName($replaced);
      }
    }
  }

  /**
   * Replaces variables in configuration name.
   *
   * The configuration name may contain one or more variables to be replaced,
   * enclosed in square brackets like '[name]' and will follow the replacement
   * rules defined by the replaceVariable() method.
   *
   * @param string $name
   *   Configuration name with variables in square brackets.
   * @param mixed $data
   *   Configuration data for the element.
   * @return string
   *   Configuration name with variables replaced.
   */
  protected function replaceName($name, $data) {
    if (preg_match_all("/\[(.*)\]/U", $name, $matches)) {
      // Build our list of '[value]' => replacement.
      $replace = array();
      foreach (array_combine($matches[0], $matches[1]) as $key => $value) {
        $replace[$key] = $this->replaceVariable($value, $data);
      }
      return strtr($name, $replace);
    }
    else {
      return $name;
    }
  }

  /**
   * Replaces variable values in included names with configuration data.
   *
   * Variable values are nested configuration keys that will be replaced by
   * their value or some of these special strings:
   * - '%key', will be replaced by the element's key.
   * - '%parent', to reference the parent element.
   *
   * There may be nested configuration keys separated by dots or more complex
   * patterns like '%parent.name' which references the 'name' value of the
   * parent element.
   *
   * Example patterns:
   * - 'name.subkey', indicates a nested value of the current element.
   * - '%parent.name', will be replaced by the 'name' value of the parent.
   * - '%parent.%key', will be replaced by the parent element's key.
   *
   * @param string $value
   *   Variable value to be replaced.
   *
   * @return string
   *   The replaced value if a replacement found or the original value if not.
   */
  protected function replaceVariable($value, $data) {
    $parts = explode('.', $value);
    // Process each value part, one at a time.
    while ($name = array_shift($parts)) {
      if (!is_array($data) || !isset($data[$name])) {
        // Key not found, return original value
        return $value;
      }
      elseif (!$parts) {
        // If no more parts left, this is the final property.
        return (string)$data[$name];
      }
      else {
        // Get nested value and continue processing.
        if ($name == '%parent') {
          // Switch replacement values with values from the parent.
          $parent = $data['%parent'];
          $data = $parent->getValue();
          // The special %parent and %key values now need to point one level up.
          if ($new_parent = $parent->getParent()) {
            $data['%parent'] = $new_parent;
            $data['%key'] = $new_parent->getName();
          }
        }
        else {
          $data = $data[$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigSchema($name) {
    // The schema system falls back on the Property class for unknown types.
    // See http://drupal.org/node/1905230
    $definition = $this->getDefinition($name);
    return is_array($definition) && ($definition['class'] != '\Drupal\Core\Config\Schema\Property');
  }

}
