<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity class for unique constraint.
 *
 * @ContentEntityType(
 *   id = "entity_test_unique_constraint",
 *   label = @Translation("unique field entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *   },
 *   base_table = "entity_test_unique_constraint",
 *   data_table = "entity_test_unique_constraint_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class EntityTestUniqueConstraint extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['field_test_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('unique_field_test'))
      ->setCardinality(3)
      ->addConstraint('UniqueField');

    $fields['field_test_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('unique_reference_test'))
      ->setCardinality(2)
      ->addConstraint('UniqueField')
      ->setSetting('target_type', 'user');
    return $fields;
  }

}
