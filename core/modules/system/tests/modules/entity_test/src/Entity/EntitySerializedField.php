<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test class for testing fields with a serialized column.
 */
#[ContentEntityType(
  id: 'entity_test_serialized_field',
  label: new TranslatableMarkup('Test serialized fields'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
  ],
  base_table: 'entity_test_serialized_fields',
  additional: [
    'serialized_field_property_names' => [
      'serialized_long' => ['value'],
    ],
  ],
)]
class EntitySerializedField extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['serialized'] = BaseFieldDefinition::create('serialized_item_test')
      ->setLabel(t('Serialized'));

    $fields['serialized_text'] = BaseFieldDefinition::create('serialized_property_item_test')
      ->setLabel(t('Serialized text'));

    $fields['serialized_long'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Serialized long string'));

    return $fields;
  }

}
