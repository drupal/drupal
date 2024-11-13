<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity class for testing default values.
 */
#[ContentEntityType(
  id: 'entity_test_default_value',
  label: new TranslatableMarkup('Test entity for default values'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'langcode' => 'langcode',
  ],
  base_table: 'entity_test_default_value',
)]
class EntityTestDefaultValue extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('shape')
      ->setLabel(t('Some custom description'))
      ->setDefaultValueCallback('entity_test_field_default_value');

    return $fields;
  }

}
