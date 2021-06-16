<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * A storage that supports entity types with field definitions.
 */
interface FieldableEntityStorageInterface extends EntityStorageInterface {

  /**
   * Determines the number of entities with values for a given field.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field for which to count data records.
   * @param bool $as_bool
   *   (Optional) Optimizes the query for checking whether there are any records
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
   * Purges a batch of field data.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The deleted field whose data is being purged.
   * @param int $batch_size
   *   The maximum number of field data records to purge before returning,
   *   relating to the count of field data records returned by
   *   \Drupal\Core\Entity\FieldableEntityStorageInterface::countFieldData().
   *
   * @return int
   *   The number of field data records that have been purged.
   */
  public function purgeFieldData(FieldDefinitionInterface $field_definition, $batch_size);

  /**
   * Performs final cleanup after all data of a field has been purged.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage being purged.
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition);

}
