<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestViewsData;
use Drupal\entity_test\Plugin\Field\ComputedReferenceTestFieldItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestCacheableIntegerItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestCacheableStringItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestFieldItemList;

/**
 * An entity used for testing computed field values.
 */
#[ContentEntityType(
  id: 'entity_test_computed_field',
  label: new TranslatableMarkup('Entity Test computed field'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'name',
  ],
  handlers: [
    'views_data' => EntityTestViewsData::class,
  ],
  links: [
    'canonical' => '/entity_test_computed_field/{entity_test_computed_field}',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_computed_field',
)]
class EntityTestComputedField extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['computed_string_field'] = BaseFieldDefinition::create('string')
      ->setLabel('Computed Field Test')
      ->setComputed(TRUE)
      ->setClass(ComputedTestFieldItemList::class);

    $fields['computed_reference_field'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Computed Reference Field Test')
      ->setComputed(TRUE)
      ->setSetting('target_type', 'entity_test')
      ->setClass(ComputedReferenceTestFieldItemList::class);

    // Cacheable metadata can either be provided via the field item properties
    // or via the field item list class directly. Add a computed string field
    // which does the former and a computed integer field which does the latter.
    $fields['computed_test_cacheable_string_field'] = BaseFieldDefinition::create('computed_test_cacheable_string_item')
      ->setLabel(new TranslatableMarkup('Computed Cacheable String Field Test'))
      ->setComputed(TRUE)
      ->setClass(ComputedTestCacheableStringItemList::class)
      ->setReadOnly(FALSE)
      ->setInternal(FALSE);
    $fields['computed_test_cacheable_integer_field'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Computed Cacheable Integer Field Test'))
      ->setComputed(TRUE)
      ->setClass(ComputedTestCacheableIntegerItemList::class)
      ->setReadOnly(FALSE)
      ->setInternal(FALSE)
      ->setDisplayOptions('view', ['weight' => 10]);

    return $fields;
  }

}
