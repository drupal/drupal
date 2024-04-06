<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;

/**
 * Provides destination plugin for field_config configuration entities.
 *
 * The Field API defines two primary data structures, FieldStorage and Field.
 * A FieldStorage defines a particular type of data that can be attached to
 * entities as a Field instance.
 *
 * The example below adds an instance of 'field_text_example' to 'article'
 * bundle (node content type). The example uses the EmptySource source plugin
 * and constant source values for the sake of simplicity. For an example on how
 * the FieldStorage 'field_text_example' can be migrated, refer to
 * \Drupal\migrate\Plugin\migrate\destination\EntityFieldStorageConfig.
 * @code
 * id: field_instance_example
 * label: Field instance example
 * source:
 *   plugin: empty
 *   constants:
 *     entity_type: node
 *     field_name: field_text_example
 *     bundle: article
 *     label: Text field example
 *     translatable: true
 *  process:
 *    entity_type: constants/entity_type
 *    field_name: constants/field_name
 *    bundle: constants/bundle
 *    label: constants/label
 *     translatable: constants/translatable
 *  destination:
 *    plugin: entity:field_config
 *  migration_dependencies:
 *    required:
 *      - field_storage_example
 * @endcode
 *
 * @see \Drupal\field\Entity\FieldConfig
 * @see \Drupal\field\Entity\FieldConfigBase
 */
#[MigrateDestination('entity:field_config')]
class EntityFieldInstance extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    if ($this->isTranslationDestination()) {
      $ids['langcode']['type'] = 'string';
    }
    return $ids;
  }

}
