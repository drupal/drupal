<?php

namespace Drupal\Core\TypedData;

/**
 * Interface for primitive data.
 *
 * @ingroup typed_data
 */
interface PrimitiveInterface {

  /**
   * Gets the primitive data value.
   *
   * @return mixed
   *   The primitive data value.
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
   *   The primitive data value cast to the correct PHP type.
   */
  public function getCastedValue();

}
