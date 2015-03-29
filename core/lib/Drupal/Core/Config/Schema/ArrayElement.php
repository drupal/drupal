<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\ArrayElement.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * Defines a generic configuration element that contains multiple properties.
 */
abstract class ArrayElement extends TypedData implements \IteratorAggregate, TypedConfigInterface {

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The configuration value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Parsed elements.
   */
  protected $elements;

  /**
   * Gets valid configuration data keys.
   *
   * @return array
   *   Array of valid configuration data keys.
   */
  protected function getAllKeys() {
    return is_array($this->value) ? array_keys($this->value) : array();
  }

  /**
   * Builds an array of contained elements.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface[]
   *   An array of elements contained in this element.
   */
  protected function parse() {
    $elements = array();
    foreach ($this->getAllKeys() as $key) {
      $value = isset($this->value[$key]) ? $this->value[$key] : NULL;
      $definition = $this->getElementDefinition($key);
      $elements[$key] = $this->createElement($definition, $value, $key);
    }
    return $elements;
  }

  /**
   * Gets data definition object for contained element.
   *
   * @param int|string $key
   *   Property name or index of the element.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected abstract function getElementDefinition($key);

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    $parts = explode('.', $name);
    $root_key = array_shift($parts);
    $elements = $this->getElements();
    if (isset($elements[$root_key])) {
      $element = $elements[$root_key];
      // If $property_name contained a dot recurse into the keys.
      while ($element && ($key = array_shift($parts)) !== NULL) {
        if ($element instanceof TypedConfigInterface) {
          $element = $element->get($key);
        }
        else {
          $element = NULL;
        }
      }
    }
    if (isset($element)) {
      return $element;
    }
    else {
      throw new \InvalidArgumentException(SafeMarkup::format("The configuration property @key doesn't exist.", array('@key' => $name)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElements() {
    if (!isset($this->elements)) {
      $this->elements = $this->parse();
    }
    return $this->elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return isset($this->value) ? $this->value : array();
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Implements IteratorAggregate::getIterator();
   */
  public function getIterator() {
    return new \ArrayIterator($this->getElements());
  }

  /**
   * Creates a contained typed configuration object.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition object.
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   * @param string $key
   *   The key of the contained element.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  protected function createElement($definition, $value, $key) {
    return $this->typedConfig->create($definition, $value, $key, $this);
  }

  /**
   * Creates a new data definition object from a type definition array and
   * actual configuration data.
   *
   * @param array $definition
   *   The base type definition array, for which a data definition should be
   *   created.
   * @param $value
   *   The value of the configuration element.
   * @param string $key
   *   The key of the contained element.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected function buildDataDefinition($definition, $value, $key) {
    return $this->typedConfig->buildDataDefinition($definition, $value, $key, $this);
  }


  /**
   * Sets the typed config manager on the instance.
   *
   * This must be called immediately after construction to enable
   * self::parseElement() and self::buildDataDefinition() to work.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   */
  public function setTypedConfig(TypedConfigManagerInterface $typed_config) {
    $this->typedConfig = $typed_config;
  }

}
