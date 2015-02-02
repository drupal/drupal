<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigManager.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Schema\ConfigSchemaDiscovery;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Manages config type plugins.
 */
class TypedConfigManager extends TypedDataManager implements TypedConfigManagerInterface {

  /**
   * A storage instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * A storage instance for reading configuration schema data.
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
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The storage object to use for reading schema data
   * @param \Drupal\Core\Config\StorageInterface $schemaStorage
   *   The storage object to use for reading schema data
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use for caching the definitions.
   */
  public function __construct(StorageInterface $configStorage, StorageInterface $schemaStorage, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler) {
    $this->configStorage = $configStorage;
    $this->schemaStorage = $schemaStorage;
    $this->setCacheBackend($cache, 'typed_config_definitions');
    $this->discovery = new ConfigSchemaDiscovery($schemaStorage);
    $this->alterInfo('config_schema_info');
    $this->moduleHandler = $module_handler;
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
    $type_definition = $this->getDefinition($name);
    $data_definition =  $this->buildDataDefinition($type_definition, $data);
    return $this->create($data_definition, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataDefinition(array $definition, $value, $name = NULL, $parent = NULL) {
    // Add default values for data type and replace variables.
    $definition += array('type' => 'undefined');

    $type = $definition['type'];
    if (strpos($type, ']')) {
      // Replace variable names in definition.
      $replace = is_array($value) ? $value : array();
      if (isset($parent)) {
        $replace['%parent'] = $parent;
      }
      if (isset($name)) {
        $replace['%key'] = $name;
      }
      $type = $this->replaceName($type, $replace);
      // Remove the type from the definition so that it is replaced with the
      // concrete type from schema definitions.
      unset($definition['type']);
    }
    // Add default values from type definition.
    $definition += $this->getDefinition($type);

    $data_definition = $this->createDataDefinition($definition['type']);

    // Pass remaining values from definition array to data definition.
    foreach ($definition as $key => $value) {
      if (!isset($data_definition[$key])) {
        $data_definition[$key] = $value;
      }
    }
    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($base_plugin_id, $exception_on_invalid = TRUE) {
    $definitions = $this->getDefinitions();
    if (isset($definitions[$base_plugin_id])) {
      $type = $base_plugin_id;
    }
    elseif (strpos($base_plugin_id, '.') && $name = $this->getFallbackName($base_plugin_id)) {
      // Found a generic name, replacing the last element by '*'.
      $type = $name;
    }
    else {
      // If we don't have definition, return the 'undefined' element.
      $type = 'undefined';
    }
    $definition = $definitions[$type];
    // Check whether this type is an extension of another one and compile it.
    if (isset($definition['type'])) {
      $merge = $this->getDefinition($definition['type'], $exception_on_invalid);
      // Preserve integer keys on merge, so sequence item types can override
      // parent settings as opposed to adding unused second, third, etc. items.
      $definition = NestedArray::mergeDeepArray(array($merge, $definition), TRUE);
      // Unset type so we try the merge only once per type.
      unset($definition['type']);
      $this->definitions[$type] = $definition;
    }
    // Add type and default definition class.
    return $definition + array(
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
      'type' => $type,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->schemaStorage->reset();
    parent::clearCachedDefinitions();
  }

  /**
   * Gets fallback configuration schema name.
   *
   * @param string $name
   *   Configuration name or key.
   *
   * @return null|string
   *   The resolved schema name for the given configuration name or key. Returns
   *   null if there is no schema name to fallback to. For example,
   *   breakpoint.breakpoint.module.toolbar.narrow will check for definitions in
   *   the following order:
   *     breakpoint.breakpoint.module.toolbar.*
   *     breakpoint.breakpoint.module.*.*
   *     breakpoint.breakpoint.module.*
   *     breakpoint.breakpoint.*.*.*
   *     breakpoint.breakpoint.*
   *     breakpoint.*.*.*.*
   *     breakpoint.*
   *   Colons are also used, for example,
   *   block.settings.system_menu_block:footer will check for definitions in the
   *   following order:
   *     block.settings.system_menu_block:*
   *     block.settings.*:*
   *     block.settings.*
   *     block.*.*:*
   *     block.*
   */
  protected function getFallbackName($name) {
    // Check for definition of $name with filesystem marker.
    $replaced = preg_replace('/([^\.:]+)([\.:\*]*)$/', '*\2', $name);
    if ($replaced != $name) {
      if (isset($this->definitions[$replaced])) {
        return $replaced;
      }
      else {
        // No definition for this level. Collapse multiple wildcards to a single
        // wildcard to see if there is a greedy match. For example,
        // breakpoint.breakpoint.*.* becomes
        // breakpoint.breakpoint.*
        $one_star = preg_replace('/\.([:\.\*]*)$/', '.*', $replaced);
        if ($one_star != $replaced && isset($this->definitions[$one_star])) {
          return $one_star;
        }
        // Check for next level. For example, if breakpoint.breakpoint.* has
        // been checked and no match found then check breakpoint.*.*
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
   * @param mixed $data
   *   Configuration data for the element.
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
    // The schema system falls back on the Undefined class for unknown types.
    $definition = $this->getDefinition($name);
    return is_array($definition) && ($definition['class'] != '\Drupal\Core\Config\Schema\Undefined');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($data_type, array $configuration = array()) {
    $instance = parent::createInstance($data_type, $configuration);
    // Enable elements to construct their own definitions using the typed config
    // manager.
    if ($instance instanceof Element) {
      $instance->setTypedConfig($this);
    }
    return $instance;
  }

}
