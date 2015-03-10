<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestMul.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test entity class using a custom langcode entity key.
 *
 * @ContentEntityType(
 *   id = "entity_test_mul_langcode_key",
 *   label = @Translation("Test entity - data table - langcode key"),
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
 *   base_table = "entity_test_mul_langcode_key",
 *   data_table = "entity_test_mul_langcode_key_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "custom_langcode_key",
 *     "default_langcode" = "custom_default_langcode_key",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_mul_langcode_key/manage/{entity_test_mul_langcode_key}",
 *     "edit-form" = "/entity_test_mul_langcode_key/manage/{entity_test_mul_langcode_key}",
 *     "delete-form" = "/entity_test/delete/entity_test_mul_langcode_key/{entity_test_mul_langcode_key}",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul_langcode_key.admin_form",
 * )
 */
class EntityTestMulLangcodeKey extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['custom_langcode_key'] = $fields['langcode'];
    unset($fields['langcode']);
    return $fields;
  }

}
