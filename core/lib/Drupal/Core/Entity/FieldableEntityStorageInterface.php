<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\FieldableEntityStorageInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

interface FieldableEntityStorageInterface extends EntityStorageInterface {

  /**
   * Reacts to the creation of a field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The definition being created.
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition);

  /**
   * Reacts to the update of a field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field being updated.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original storage definition; i.e., the definition before the update.
   *
   * @throws \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException
   *   Thrown when the update to the field is forbidden.
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original);

  /**
   * Reacts to the deletion of a field storage definition.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field being deleted.
   *
   * @see purgeFieldData()
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition);

  /**
   * Reacts to the creation of a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition created.
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition);

  /**
   * Reacts to the update of a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition being updated.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The original field definition; i.e., the definition before the update.
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original);

  /**
   * Reacts to the deletion of a field.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition being deleted.
   *
   * @see purgeFieldData()
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition);

  /**
   * Reacts to a bundle being created.
   *
   * @param string $bundle
   *   The name of the bundle created.
   */
  public function onBundleCreate($bundle);

  /**
   * Reacts to a bundle being renamed.
   *
   * This method runs before fields are updated with the new bundle name.
   *
   * @param string $bundle
   *   The name of the bundle being renamed.
   * @param string $bundle_new
   *   The new name of the bundle.
   */
  public function onBundleRename($bundle, $bundle_new);

  /**
   * Reacts to a bundle being deleted.
   *
   * This method runs before fields are deleted.
   *
   * @param string $bundle
   *   The name of the bundle being deleted.
   */
  public function onBundleDelete($bundle);

  /**
   * Purges a batch of field data.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The deleted field whose data is being purged.
   * @param $batch_size
   *   The maximum number of field data records to purge before returning,
   *   relating to the count of field data records returned by
   *   \Drupal\Core\Entity\FieldableEntityStorageInterface::countFieldData().
   *
   * @return int
   *   The number of field data records that have been purged.
   */
  public function purgeFieldData(FieldDefinitionInterface $field_definition, $batch_size);

  /**
   * Determines the number of entities with values for a given field.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field for which to count data records.
   * @param bool $as_bool
   *   (Optional) Optimises the query for checking whether there are any records
   *   or not. Defaults to FALSE.
   *
   * @return bool|int
   *   The number of entities. If $as_bool parameter is TRUE then the
   *   value will either be TRUE or FALSE.
   *
   * @see \Drupal\Core\Entity\FieldableEntityStorageInterface::purgeFieldData()
   */
  public function countFieldData($storage_definition, $as_bool = FALSE);

  /**
   * Performs final cleanup after all data of a field has been purged.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field being purged.
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition);

}
