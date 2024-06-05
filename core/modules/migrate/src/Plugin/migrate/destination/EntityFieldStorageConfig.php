<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;

/**
 * Provides destination plugin for field_storage_config configuration entities.
 *
 * The Field API defines two primary data structures, FieldStorage and Field.
 * A FieldStorage defines a particular type of data that can be attached to
 * entities as a Field instance.
 *
 * The example below creates a storage for a simple text field. The example uses
 * the EmptySource source plugin and constant source values for the sake of
 * simplicity.
 * @code
 * id: field_storage_example
 * label: Field storage example
 * source:
 * plugin: empty
 * constants:
 *   entity_type: node
 *   id: node.field_text_example
 *   field_name: field_text_example
 *   type: string
 *   cardinality: 1
 *   settings:
 *     max_length: 10
 *   langcode: en
 *   translatable: true
 * process:
 *   entity_type: constants/entity_type
 *   id: constants/id
 *   field_name: constants/field_name
 *   type: constants/type
 *   cardinality: constants/cardinality
 *   settings: constants/settings
 *   langcode: constants/langcode
 *   translatable: constants/translatable
 * destination:
 *   plugin: entity:field_storage_config
 * @endcode
 *
 * For a full list of the properties of a FieldStorage configuration entity,
 * refer to \Drupal\field\Entity\FieldStorageConfig.
 *
 * For an example on how to migrate a Field instance of this FieldStorage,
 * refer to \Drupal\migrate\Plugin\migrate\destination\EntityFieldInstance.
 */
#[MigrateDestination('entity:field_storage_config')]
class EntityFieldStorageConfig extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    // @todo Remove conditional. https://www.drupal.org/node/3004574
    if ($this->isTranslationDestination()) {
      $ids['langcode']['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    if ($this->isTranslationDestination()) {
      $language = $destination_identifier['langcode'];
      unset($destination_identifier['langcode']);
      $destination_identifier = [
        implode('.', $destination_identifier),
        'langcode' => $language,
      ];
    }
    else {
      $destination_identifier = [implode('.', $destination_identifier)];
    }
    parent::rollback($destination_identifier);
  }

}
