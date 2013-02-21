<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigManager.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Config\Schema\SchemaDiscovery;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Manages config type plugins.
 */
class TypedConfigManager extends TypedDataManager {

  /**
   * A storage controller instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The storage controller object to use for reading schema data
   * @param \Drupal\Core\Config\StorageInterface $schemaStorage
   *   The storage controller object to use for reading schema data
   */
  public function __construct(StorageInterface $configStorage, StorageInterface $schemaStorage) {
    $this->configStorage = $configStorage;
    $this->discovery = new SchemaDiscovery($schemaStorage);
    $this->factory = new TypedConfigElementFactory($this->discovery);
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
        $replace['%parent'] = $parent->getValue();
      }
      if (isset($name)) {
        $replace['%key'] = $name;
      }
      $definition['type'] = $this->replaceName($definition['type'], $replace);
    }
    // Create typed config object.
    return parent::create($definition, $value, $name, $parent);
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
  protected static function replaceName($name, $data) {
    if (preg_match_all("/\[(.*)\]/U", $name, $matches)) {
      // Build our list of '[value]' => replacement.
      foreach (array_combine($matches[0], $matches[1]) as $key => $value) {
        $replace[$key] = self::replaceVariable($value, $data);
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
  protected static function replaceVariable($value, $data) {
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
        $data = $data[$name];
      }
    }
  }

}
