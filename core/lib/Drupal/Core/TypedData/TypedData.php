<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedData.
 */

namespace Drupal\Core\TypedData;

/**
 * The abstract base class for typed data.
 *
 * Classes deriving from this base class have to declare $value
 * or override getValue() or setValue().
 */
abstract class TypedData implements TypedDataInterface {

  /**
   * The data definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a TypedData object given its definition.
   *
   * @param array $definition
   *   The data definition.
   *
   * @see Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getType().
   */
  public function getType() {
    return $this->definition['type'];
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getDefinition().
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getString().
   */
  public function getString() {
    return (string) $this->getValue();
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getConstraints().
   */
  public function getConstraints() {
    // @todo: Add the typed data manager as proper dependency.
    return typed_data()->getConstraints($this->definition);
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::validate().
   */
  public function validate() {
    // @todo: Add the typed data manager as proper dependency.
    return typed_data()->getValidator()->validate($this);
  }
}
