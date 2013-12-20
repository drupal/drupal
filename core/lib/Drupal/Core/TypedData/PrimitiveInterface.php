<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\PrimitiveInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for primitive data.
 */
interface PrimitiveInterface {

  /**
   * Gets the primitive data value.
   *
   * @return mixed
   */
  public function getValue();

  /**
   * Sets the primitive data value.
   *
   * @param mixed|null $value
   *   The value to set in the format as documented for the data type or NULL to
   *   unset the data value.
   */
  public function setValue($value);

  /**
   * Gets the primitive data value casted to the correct PHP type.
   *
   * @return mixed
   */
  public function getCastedValue();
}
