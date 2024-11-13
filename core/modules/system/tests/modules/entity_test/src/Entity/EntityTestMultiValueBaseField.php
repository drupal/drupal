<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\views\EntityViewsData;

// cspell:ignore basefield

/**
 * Defines an entity type with a multivalue base field.
 */
#[ContentEntityType(
  id: 'entity_test_multivalue_basefield',
  label: new TranslatableMarkup('Entity Test with a multivalue base field'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'views_data' => EntityViewsData::class,
  ],
  base_table: 'entity_test_multivalue_basefield',
  data_table: 'entity_test_multivalue_basefield_field_data',
)]
class EntityTestMultiValueBaseField extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['name']->setCardinality(2);

    return $fields;
  }

}
