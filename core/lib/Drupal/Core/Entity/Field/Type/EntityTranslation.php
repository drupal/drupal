<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\Type\EntityTranslation.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\TypedData\Type\TypedData;
use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * Makes translated entity properties available via the Field API.
 */
class EntityTranslation extends TypedData implements IteratorAggregate, AccessibleInterface, ComplexDataInterface, ContextAwareInterface {

  /**
   * The array of translated properties, each being an instance of
   * FieldInterface.
   *
   * @var array
   */
  protected $properties = array();

  /**
   * The language code of the translation.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $parent;

  /**
   * Whether the entity translation acts in strict mode.
   *
   * @var boolean
   */
  protected $strict = TRUE;

  /**
   * Returns whether the entity translation acts in strict mode.
   *
   * @return boolean
   *   Whether the entity translation acts in strict mode.
   */
  public function getStrictMode() {
    return $this->strict;
  }

  /**
   * Sets whether the entity translation acts in strict mode.
   *
   * @param boolean $strict
   *   Whether the entity translation acts in strict mode.
   *
   * @see \Drupal\Core\TypedData\TranslatableInterface::getTranslation()
   */
  public function setStrictMode($strict = TRUE) {
    $this->strict = $strict;
  }

  /**
   * Implements ContextAwareInterface::getName().
   */
  public function getName() {
    // The name of the translation is the language code.
    return $this->langcode;
  }

  /**
   * Implements ContextAwareInterface::setName().
   */
  public function setName($name) {
    // The name of the translation is the language code.
    $this->langcode = $name;
  }

  /**
   * Implements ContextAwareInterface::getParent().
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Implements ContextAwareInterface::setParent().
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }
  /**
   * Implements TypedDataInterface::getValue().
   */
  public function getValue() {
    // The value of the translation is the array of translated property objects.
    return $this->properties;
  }

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($values) {
    $this->properties = $values;
  }

  /**
   * Implements TypedDataInterface::getString().
   */
  public function getString() {
    $strings = array();
    foreach ($this->getProperties() as $property) {
      $strings[] = $property->getString();
    }
    return implode(', ', array_filter($strings));
  }

  /**
   * Implements TypedDataInterface::get().
   */
  public function get($property_name) {
    $definitions = $this->getPropertyDefinitions();
    if (!isset($definitions[$property_name])) {
      throw new InvalidArgumentException(format_string('Field @name is unknown or not translatable.', array('@name' => $property_name)));
    }
    return $this->properties[$property_name];
  }

  /**
   * Implements ComplexDataInterface::set().
   */
  public function set($property_name, $value) {
    $this->get($property_name)->setValue($value);
  }

  /**
   * Implements ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    $properties = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if ($include_computed || empty($definition['computed'])) {
        $properties[$name] = $this->get($name);
      }
    }
    return $properties;
  }

  /**
   * Magic getter: Gets the translated property.
   */
  public function __get($name) {
    return $this->get($name);
  }

  /**
   * Magic getter: Sets the translated property.
   */
  public function __set($name, $value) {
    $this->get($name)->setValue($value);
  }

  /**
   * Implements IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new ArrayIterator($this->getProperties());
  }

  /**
   * Implements ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    $definitions = $this->getPropertyDefinitions();
    return isset($definitions[$name]) ? $definitions[$name] : FALSE;
  }

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    $definitions = array();
    foreach ($this->parent->getPropertyDefinitions() as $name => $definition) {
      if (!empty($definition['translatable']) || !$this->strict) {
        $definitions[$name] = $definition;
      }
    }
    return $definitions;
  }

  /**
   * Implements ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    return $this->getValue();
  }

  /**
   * Implements ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    foreach ($values as $name => $value) {
      $this->get($name)->setValue($value);
    }
  }

  /**
   * Implements ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    foreach ($this->getProperties() as $property) {
      if ($property->getValue() !== NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Implements AccessibleInterface::access().
   */
  public function access(\Drupal\user\User $account = NULL) {
    // @todo implement
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate($value = NULL) {
    // @todo implement
  }
}
