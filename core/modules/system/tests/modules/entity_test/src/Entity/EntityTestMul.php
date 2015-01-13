<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestMul.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mul",
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
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_mul/manage/{entity_test_mul}",
 *     "edit-form" = "/entity_test_mul/manage/{entity_test_mul}",
 *     "delete-form" = "/entity_test/delete/entity_test_mul/{entity_test_mul}",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul.admin_form",
 * )
 */
class EntityTestMul extends EntityTest {

}
