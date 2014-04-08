<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Mapping.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Component\Utility\String;

/**
 * Defines a mapping configuration element.
 *
 * Wraps configuration data and metadata allowing access to configuration data
 * using the ComplexDataInterface API. This object may contain any number and
 * type of nested properties.
 */
class Mapping extends ArrayElement implements ComplexDataInterface {

  /**
   * Overrides ArrayElement::parse()
   */
  protected function parse() {
    $elements = array();
    foreach ($this->definition['mapping'] as $key => $definition) {
      if (isset($this->value[$key]) || array_key_exists($key, $this->value)) {
        $elements[$key] = $this->parseElement($key, $this->value[$key], $definition);
      }
    }
    return $elements;
  }

  /**
   * Implements Drupal\Core\TypedData\ComplexDataInterface::get().
   *
   * Since all configuration objects are mappings the function will except a dot
   * delimited key to access nested values, for example, 'page.front'.
   */
  public function get($property_name) {
    $parts = explode('.', $property_name);
    $root_key = array_shift($parts);
    $elements = $this->getElements();
    if (isset($elements[$root_key])) {
      $element = $elements[$root_key];
    }
    else {
      throw new SchemaIncompleteException(String::format("The configuration property @key doesn't exist.", array('@key' => $property_name)));
    }

    // If $property_name contained a dot recurse into the keys.
    foreach ($parts as $key) {
     if (!is_object($element) || !method_exists($element, 'get')) {
        throw new SchemaIncompleteException(String::format("The configuration property @key does not exist.", array('@key' => $property_name)));
      }
      $element = $element->get($key);
    }
    return $element;
  }

  /**
   * Implements Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    // Set the data into the configuration array but behave according to the
    // interface specification when we've got a null value.
    if (isset($value)) {
      $this->value[$property_name] = $value;
      $property = $this->get($property_name);
    }
    else {
      // In these objects, when clearing the value, the property is gone.
      // As this needs to return a property, we get it before we delete it.
      $property = $this->get($property_name);
      unset($this->value[$property_name]);
      $property->setValue($value);
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
    return $property;
  }

  /**
   * Implements Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    return $this->getElements();
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->getValue();
  }

  /**
   * Gets the definition of a contained property.
   *
   * @param string $name
   *   The name of property.
   *
   * @return array|null
   *   The definition of the property or NULL if the property does not exist.
   */
  public function getPropertyDefinition($name) {
    if (isset($this->definition['mapping'][$name])) {
      return $this->definition['mapping'][$name];
    }
  }

  /**
   * Gets an array of property definitions of contained properties.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   */
  public function getPropertyDefinitions() {
    $list = array();
    foreach ($this->getAllKeys() as $key) {
      $list[$key] = $this->getPropertyDefinition($key);
    }
    return $list;
  }

  /**
   * Implements Drupal\Core\TypedData\ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    return empty($this->value);
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::onChange().
   */
  public function onChange($property_name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
