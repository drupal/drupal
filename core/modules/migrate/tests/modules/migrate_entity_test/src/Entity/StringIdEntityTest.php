<?php

namespace Drupal\migrate_entity_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a content entity type that has a string ID.
 *
 * @ContentEntityType(
 *   id = "migrate_string_id_entity_test",
 *   label = @Translation("String id entity test"),
 *   base_table = "migrate_entity_test_string_id",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class StringIdEntityTest extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    return [
      'id' => BaseFieldDefinition::create('integer')
        ->setSetting('size', 'big')
        ->setLabel('ID'),
      'version' => BaseFieldDefinition::create('string')
        ->setLabel('Version'),
    ];
  }

}
