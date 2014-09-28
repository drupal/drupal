<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface.
 */

namespace Drupal\Core\Entity\Schema;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * A storage schema that supports entity types with dynamic field definitions.
 *
 * A storage schema that implements this interface can react to the entity
 * type's field definitions changing, due to modules being installed or
 * uninstalled, or via field UI, or via code changes to the entity class.
 *
 * For example, configurable fields defined and exposed by field.module.
 */
interface DynamicallyFieldableEntityStorageSchemaInterface extends EntityStorageSchemaInterface, FieldStorageDefinitionListenerInterface {

  /**
   * Checks if the changes to the storage definition requires schema changes.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The updated field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original field storage definition.
   *
   * @return bool
   *   TRUE if storage schema changes are required, FALSE otherwise.
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original);

  /**
   * Checks if existing data would be lost if the schema changes were applied.
   *
   * If there are no schema changes needed, then no data needs to be migrated,
   * but it is not the responsibility of this function to recheck what
   * requiresFieldStorageSchemaChanges() checks. Rather, the meaning of what
   * this function returns when requiresFieldStorageSchemaChanges() returns
   * FALSE is undefined. Callers are expected to only call this function when
   * requiresFieldStorageSchemaChanges() is TRUE.
   *
   * This function can return FALSE if any of these conditions apply:
   * - There are no existing entities for the entity type to which this field
   *   is attached.
   * - There are existing entities, but none with existing values for this
   *   field.
   * - There are existing field values, but the schema changes can be applied
   *   without losing them (e.g., if the schema changes can be performed by
   *   altering tables rather than dropping and recreating them).
   * - The only field values that would be lost are ones that are not valid for
   *   the new definition (e.g., if changing a field from revisionable to
   *   non-revisionable, then it's okay to drop data for the non-default
   *   revision).
   *
   * When this function returns FALSE, site administrators will be unable to
   * perform an automated update, and will instead need to perform a site
   * migration or invoke some custom update process.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The updated field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original field storage definition.
   *
   * @return bool
   *   TRUE if data migration is required, FALSE otherwise.
   *
   * @see self::requiresFieldStorageSchemaChanges()
   */
  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original);

  /**
   * Performs final cleanup after all data of a field has been purged.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field being purged.
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition);

}
