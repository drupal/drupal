<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * Provides entity field storage configuration plugin.
 *
 * @MigrateDestination(
 *   id = "entity:field_storage_config"
 * )
 */
class EntityFieldStorageConfig extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    return $ids;
  }

}
