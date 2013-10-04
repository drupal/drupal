<?php

/**
 * @file
 * Definition of Drupal\entity_test\Entity\EntityTestMulRev.
 */

namespace Drupal\entity_test\Entity;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @EntityType(
 *   id = "entity_test_mulrev",
 *   label = @Translation("Test entity - revisions and data table"),
 *   module = "entity_test",
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "entity_test_mulrev",
 *   data_table = "entity_test_mulrev_property_data",
 *   revision_table = "entity_test_mulrev_property_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type"
 *   },
 *   menu_base_path = "entity_test_mulrev/manage/%entity_test_mulrev"
 * )
 */
class EntityTestMulRev extends EntityTestRev {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['revision_id'] = array(
      'label' => t('ID'),
      'description' => t('The version id of the test entity.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['default_langcode'] = array(
      'label' => t('Default language'),
      'description' => t('Flag to inditcate whether this is the default language.'),
      'type' => 'boolean_field',
    );
    return $fields;
  }

}
