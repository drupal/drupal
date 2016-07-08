<?php

namespace Drupal\entity_test\Entity;

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
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_mul_langcode_key",
 *   data_table = "entity_test_mul_langcode_key_field_data",
 *   admin_permission = "administer entity_test content",
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
 *     "add-form" = "/entity_test_mul_langcode_key/add",
 *     "canonical" = "/entity_test_mul_langcode_key/manage/{entity_test_mul_langcode_key}",
 *     "edit-form" = "/entity_test_mul_langcode_key/manage/{entity_test_mul_langcode_key}/edit",
 *     "delete-form" = "/entity_test/delete/entity_test_mul_langcode_key/{entity_test_mul_langcode_key}",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul_langcode_key.admin_form",
 * )
 */
class EntityTestMulLangcodeKey extends EntityTest {

}
