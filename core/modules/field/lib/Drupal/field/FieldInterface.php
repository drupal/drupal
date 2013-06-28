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
interface FieldInterface extends ConfigEntityInterface, FieldDefinitionInterface, \ArrayAccess, \Serializable {

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
   * @see \Drupal\field\Plugin\Core\Entity\FieldInterface::getSchema()
   */
  public function getColumns();

  /**
   * Returns information about how the storage backend stores the field data.
   *
   * The content of the returned value depends on the storage backend, and some
   * storage backends might provide no information.
   *
   * It is strongly discouraged to use this information to perform direct write
   * operations to the field data storage, bypassing the regular field saving
   * APIs.
   *
   * Example return value for the default field_sql_storage backend:
   * - 'sql'
   *   - FIELD_LOAD_CURRENT
   *     - Table name (string).
   *       - Table schema (array)
   *   - FIELD_LOAD_REVISION
   *     - Table name (string).
   *       - Table schema (array).
   *
   * @return array
   *   The storage details.
   *    - The first dimension is a store type (sql, solr, etc).
   *    - The second dimension indicates the age of the values in the store
   *      FIELD_LOAD_CURRENT or FIELD_LOAD_REVISION.
   *    - Other dimensions are specific to the field storage backend.
   */
  public function getStorageDetails();

  /**
   * Returns the list of bundles where the field has instances.
   *
   * @return array
   *   An array keyed by entity type names, whose values are arrays of bundle
   *   names.
   */
  public function getBundles();

}
