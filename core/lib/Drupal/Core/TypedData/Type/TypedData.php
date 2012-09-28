<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\Type\TypedData.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedDataInterface;

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
   * Implements TypedDataInterface::__construct().
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * Implements TypedDataInterface::getType().
   */
  public function getType() {
    return $this->definition['type'];
  }

  /**
   * Implements TypedDataInterface::getDefinition().
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Implements TypedDataInterface::getValue().
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Implements TypedDataInterface::getString().
   */
  public function getString() {
    return (string) $this->getValue();
  }
}
