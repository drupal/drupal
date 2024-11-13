<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * An entity used for testing map base field values.
 */
#[ContentEntityType(
  id: 'entity_test_map_field',
  label: new TranslatableMarkup('Entity Test map field'),
  entity_keys: [
    'uuid' => 'uuid',
    'id' => 'id',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_map_field',
)]
class EntityTestMapField extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    return $fields;
  }

}
