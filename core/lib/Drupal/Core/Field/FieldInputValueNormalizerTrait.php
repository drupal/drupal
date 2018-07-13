<?php

namespace Drupal\Core\Field;

/**
 * A trait used to assist in the normalization of raw input field values.
 *
 * @internal
 *
 * @see \Drupal\Core\Field\FieldConfigBase
 * @see \Drupal\Core\Field\BaseFieldDefinition
 */
trait FieldInputValueNormalizerTrait {

  /**
   * Ensure a field value is transformed into a format keyed by delta.
   *
   * @param mixed $value
   *   The raw field value to normalize.
   * @param string $main_property_name
   *   The main field property name.
   *
   * @return array
   *   A field value normalized into a format keyed by delta.
   */
  protected static function normalizeValue(&$value, $main_property_name) {
    if (!isset($value) || $value === NULL) {
      return [];
    }
    if (!is_array($value)) {
      if ($main_property_name === NULL) {
        throw new \InvalidArgumentException('A main property is required when normalizing scalar field values.');
      }
      return [[$main_property_name => $value]];
    }
    if (!empty($value) && !is_numeric(array_keys($value)[0])) {
      return [0 => $value];
    }
    return $value;
  }

}
