<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestMulDefaultValue.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mul_default_value",
 *   label = @Translation("Test entity - data table"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "entity_test_mul",
 *   data_table = "entity_test_mul_property_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "canonical" = "entity.entity_test_mul.edit_form",
 *     "edit-form" = "entity.entity_test_mul.edit_form",
 *     "delete-form" = "entity.entity_test_mul.delete_form",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul.admin_form",
 * )
 */
class EntityTestMulDefaultValue extends EntityTestMul {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('shape')
      ->setLabel(t('Some custom description'))
      ->setTranslatable(TRUE)
      ->setDefaultValueCallback('entity_test_field_default_value');

    return $fields;
  }

}
