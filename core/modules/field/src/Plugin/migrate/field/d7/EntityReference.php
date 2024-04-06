<?php

namespace Drupal\field\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore entityreference

/**
 * MigrateField plugin for Drupal 7 entity_reference fields.
 */
#[MigrateField(
  id: 'entityreference',
  core: [7],
  type_map: [
    'entityreference' => 'entity_reference',
  ],
  source_module: 'entityreference',
  destination_module: 'core',
)]
class EntityReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'entityreference_label' => 'entity_reference_label',
      'entityreference_entity_id' => 'entity_reference_entity_id',
      'entityreference_entity_view' => 'entity_reference_entity_view',
    ];
  }

}
