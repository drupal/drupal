<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Type\EntityTranslation.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\user\UserInterface;
use ArrayIterator;
use Drupal\Core\TypedData\TypedDataInterface;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * Allows accessing and updating translated entity fields.
 *
 * Via this object translated entity fields may be read and updated in the same
 * way as untranslatable entity fields on the entity object.
 */
class EntityTranslation extends TypedData implements IteratorAggregate, AccessibleInterface, ComplexDataInterface {

  /**
   * The array of translated fields, each being an instance of
   * \Drupal\Core\Entity\FieldInterface.
   *
   * @var array
   */
  protected $fields = array();

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
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue() {
    // The plain value of the translation is the array of translated field
    // objects.
    return $this->fields;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   */
  public function setValue($values, $notify = TRUE) {
    // Notify the parent of any changes to be made.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
    $this->fields = $values;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getString().
   */
  public function getString() {
    $strings = array();
    foreach ($this->getProperties() as $property) {
      $strings[] = $property->getString();
    }
    return implode(', ', array_filter($strings));
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::get().
   */
  public function get($property_name) {
    $definitions = $this->getPropertyDefinitions();
    if (!isset($definitions[$property_name])) {
      throw new InvalidArgumentException(format_string('Field @name is unknown or not translatable.', array('@name' => $property_name)));
    }
    return $this->fields[$property_name];
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    $this->get($property_name)->setValue($value, FALSE);
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
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
   * Magic method: Gets a translated field.
   */
  public function __get($name) {
    return $this->get($name);
  }

  /**
   * Magic method: Sets a translated field.
   */
  public function __set($name, $value) {
    $this->get($name)->setValue($value);
  }

  /**
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new ArrayIterator($this->getProperties());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    $definitions = $this->getPropertyDefinitions();
    if (isset($definitions[$name])) {
      return $definitions[$name];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
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
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    return $this->getValue();
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    foreach ($values as $name => $value) {
      $this->get($name)->setValue($value);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::isEmpty().
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
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::onChange().
   */
  public function onChange($property_name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\AccessibleInterface::access().
   */
  public function access($operation = 'view', UserInterface $account = NULL) {
    // Determine the language code of this translation by cutting of the
    // leading "@" from the property name to get the langcode.
    // @todo Add a way to set and get the langcode so that's more obvious what
    // we're doing here.
    $langcode = substr($this->getName(), 1);
    return \Drupal::entityManager()
      ->getAccessController($this->parent->entityType())
      ->access($this->parent, $operation, $langcode, $account);
  }
}
