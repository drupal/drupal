<?php
/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateEntityDestinationFieldInterface
 */

namespace Drupal\migrate\Plugin;

use Drupal\field\Entity\FieldInstance;

/**
 * Handle the importing of a specific configurable field type.
 */
interface MigrateEntityDestinationFieldInterface {

  /**
   * Convert an array of values into an array structure fit for entity_create.
   *
   * @param \Drupal\field\Entity\FieldInstance $instance
   *   The field instance. For example, this can be used to check for required.
   * @param array $values
   *   The array of values.
   * @return array|NULL
   *   This will be set in the $values array passed to entity_create() as the
   *   value of a configurable field of the type this class handles.
   */
  public function import(FieldInstance $instance, array $values = NULL);

}
