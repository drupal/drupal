<?php
/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateEntityDestinationFieldInterface
 */

namespace Drupal\migrate\Plugin;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Handle the importing of a specific configurable field type.
 */
interface MigrateEntityDestinationFieldInterface {

  /**
   * Convert an array of values into an array structure fit for entity_create.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition. For example, this can be used to check for
   *   required values.
   * @param array $values
   *   The array of values.
   * @return array|NULL
   *   This will be set in the $values array passed to entity_create() as the
   *   value of a configurable field of the type this class handles.
   */
  public function import(FieldDefinitionInterface $field_definition, array $values = NULL);

}
