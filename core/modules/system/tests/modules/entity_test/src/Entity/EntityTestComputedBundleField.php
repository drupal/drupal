<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\entity_test\Plugin\Field\ComputedReferenceTestFieldItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestBundleFieldItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestCacheableStringItemList;
use Drupal\entity_test\Plugin\Field\ComputedTestFieldItemList;

/**
 * An entity used for testing computed bundle field values.
 *
 * @ContentEntityType(
 *   id = "entity_test_comp_bund_fld",
 *   label = @Translation("Entity Test computed bundle field"),
 *   base_table = "entity_test_comp_bund_fld",
 *   handlers = {
 *     "views_data" = "Drupal\entity_test\EntityTestViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "bundle" = "type",
 *   },
 *   admin_permission = "administer entity_test content",
 * )
 */
class EntityTestComputedBundleField extends EntityTest {

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

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $computed_field_bundles = [
      'entity_test_comp_bund_fld_bund',
      'entity_test_comp_bund_fld_bund_2',
    ];

    if (in_array($bundle, $computed_field_bundles, TRUE)) {
      // @todo Use the proper FieldStorageDefinition class instead
      // https://www.drupal.org/node/2280639.
      $storageDefinition = FieldStorageDefinition::create('string')
        ->setName('computed_bundle_field')
        ->setTargetEntityTypeId($entity_type->id())
        ->setComputed(TRUE)
        ->setClass(ComputedTestBundleFieldItemList::class);
      $fields['computed_bundle_field'] = FieldDefinition::createFromFieldStorageDefinition($storageDefinition)
        ->setLabel(t('A computed Bundle Field Test'))
        ->setComputed(TRUE)
        ->setClass(ComputedTestBundleFieldItemList::class);
    }

    return $fields;
  }

}
