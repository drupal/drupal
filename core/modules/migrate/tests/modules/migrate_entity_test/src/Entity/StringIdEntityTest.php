<?php

declare(strict_types=1);

namespace Drupal\migrate_entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a content entity type that has a string ID.
 */
#[ContentEntityType(
  id: 'migrate_string_id_entity_test',
  label: new TranslatableMarkup('String id entity test'),
  entity_keys: [
    'id' => 'id',
  ],
  base_table: 'migrate_entity_test_string_id',
)]
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
