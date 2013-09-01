<?php

/**
 * @file
 * Contains \Drupal\field\FieldInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field entity.
 */
interface FieldInterface extends ConfigEntityInterface, FieldDefinitionInterface, \ArrayAccess {

  /**
   * Returns the field schema.
   *
   * @return array
   *   The field schema, as an array of key/value pairs in the format returned
   *   by hook_field_schema():
   *   - columns: An array of Schema API column specifications, keyed by column
   *     name. This specifies what comprises a single value for a given field.
   *     No assumptions should be made on how storage backends internally use
   *     the original column name to structure their storage.
   *   - indexes: An array of Schema API index definitions. Some storage
   *     backends might not support indexes.
   *   - foreign keys: An array of Schema API foreign key definitions. Note,
   *     however, that depending on the storage backend specified for the field,
   *     the field data is not necessarily stored in SQL.
   */
  public function getSchema();

  /**
   * Returns the field columns, as defined in the field schema.
   *
   * @return array
   *   The array of field columns, keyed by column name, in the same format
   *   returned by getSchema().
   *
   * @see \Drupal\field\Entity\FieldInterface::getSchema()
   */
  public function getColumns();

  /**
   * Returns the list of bundles where the field has instances.
   *
   * @return array
   *   An array keyed by entity type names, whose values are arrays of bundle
   *   names.
   */
  public function getBundles();

}
