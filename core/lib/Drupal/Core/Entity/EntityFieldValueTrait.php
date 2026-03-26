<?php

declare(strict_types=1);

namespace Drupal\Core\Entity;

use Drupal\Core\Language\LanguageInterface;

/**
 * Adds a ::getFieldValue() method suitable for use with content entities.
 *
 * @ingroup entity_api
 */
trait EntityFieldValueTrait {

  /**
   * Gets the value of a field property directly, bypassing the typed data API.
   *
   * For certain use cases, it can be desirable to avoid the overhead of
   * creating FieldItemList and ItemList objects in order to access certain
   * properties of entities. This is particularly true where the access would be
   * the only interaction with the entity system for an entire response, or
   * where a very large number of entities are being dealt with at once. This
   * method can be used in those cases, but it is marked protected and @internal
   * to discourage use, since it is not robust for dealing with the full
   * lifecycle of entity creation or updates, or for computed fields and
   * properties.
   *
   * @param string $field_name
   *   The field name.
   * @param string $property
   *   The field property, usually 'value' for single property field types.
   * @param int $delta
   *   The field delta.
   *
   * @return mixed
   *   The value of the field property, or NULL.
   *
   * @internal
   */
  protected function getFieldValue(string $field_name, string $property, int $delta = 0): mixed {
    // Attempt to get the value from the values directly if the field is not
    // initialized yet.
    if (!isset($this->fields[$field_name]) && isset($this->values[$field_name])) {
      $langcode = match(TRUE) {
        \array_key_exists($this->activeLangcode, $this->values[$field_name]) => $this->activeLangcode,
        \array_key_exists(LanguageInterface::LANGCODE_DEFAULT, $this->values[$field_name]) => LanguageInterface::LANGCODE_DEFAULT,
        default => NULL,
      };

      if ($langcode !== NULL) {
        // If there are field values, try to get the property value.
        return match (TRUE) {
          // Configurable/Multi-value fields are stored differently, try
          // accessing with delta and property first, then without delta.
          isset($this->values[$field_name][$langcode][$delta][$property]) => $this->values[$field_name][$langcode][$delta][$property],
          isset($this->values[$field_name][$langcode][$property]) => $this->values[$field_name][$langcode][$property],
          // If the values are scalar, just return that.
          !is_array($this->values[$field_name][$langcode]) => $this->values[$field_name][$langcode],
          default => NULL,
        };
      }
    }

    // Fall back to access the property through the field object.
    $field_value = $this->get($field_name)->get($delta);
    if ($field_value !== NULL) {
      return $field_value->$property;
    }
    // $delta does not exist in value list.
    return NULL;
  }

}
