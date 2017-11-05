<?php

namespace Drupal\Core\TypedData;

/**
 * Helper class for internal properties.
 */
class TypedDataInternalPropertiesHelper {

  /**
   * Gets an array non-internal properties from a complex data object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   The complex data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface[]
   *   The non-internal properties, keyed by property name.
   */
  public static function getNonInternalProperties(ComplexDataInterface $data) {
    return array_filter($data->getProperties(TRUE), function (TypedDataInterface $property) {
      return !$property->getDataDefinition()->isInternal();
    });
  }

}
