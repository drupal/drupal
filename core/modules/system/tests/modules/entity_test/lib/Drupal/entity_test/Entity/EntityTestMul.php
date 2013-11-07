<?php

/**
 * @file
 * Definition of Drupal\entity_test\Entity\EntityTestMul.
 */

namespace Drupal\entity_test\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @EntityType(
 *   id = "entity_test_mul",
 *   label = @Translation("Test entity - data table"),
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "entity_test_mul",
 *   data_table = "entity_test_mul_property_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   },
 *   route_base_path = "entity_test_mul/structure/{bundle}",
 *   links = {
 *     "canonical" = "/entity_test_mul/manage/{entity_test_mul}",
 *     "edit-form" = "/entity_test_mul/manage/{entity_test_mul}"
 *   }
 * )
 */
class EntityTestMul extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['default_langcode'] = array(
      'label' => t('Default language'),
      'description' => t('Flag to indicate whether this is the default language.'),
      'type' => 'boolean_field',
    );
    return $fields;
  }

}
