<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Plugin\Field\ComputedReferenceTestFieldItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestCacheableStringItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestFieldItemList;

/**
 * An entity used for testing computed field values.
 *
 * @ContentEntityType(
 *   id = "entity_test_computed_field",
 *   label = @Translation("Entity Test computed field"),
 *   base_table = "entity_test_computed_field",
 *   handlers = {
 *     "views_data" = "Drupal\entity_test\EntityTestViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   admin_permission = "administer entity_test content",
 *   links = {
 *     "canonical" = "/entity_test_computed_field/{entity_test_computed_field}",
 *   },
 * )
 */
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

    $fields['computed_test_cacheable_string_field'] = BaseFieldDefinition::create('computed_test_cacheable_string_item')
      ->setLabel(new TranslatableMarkup('Computed Cacheable String Field Test'))
      ->setComputed(TRUE)
      ->setClass(ComputedTestCacheableStringItemList::class)
      ->setReadOnly(FALSE)
      ->setInternal(FALSE);

    return $fields;
  }

}
