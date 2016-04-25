<?php

namespace Drupal\entity_test\Entity;

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
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_mul",
 *   data_table = "entity_test_mul_property_data",
 *   admin_permission = "administer entity_test content",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-page" = "/entity_test_mul/add",
 *     "add-form" = "/entity_test_mul/add/{type}",
 *     "canonical" = "/entity_test_mul/manage/{entity_test_mul}",
 *     "edit-form" = "/entity_test_mul/manage/{entity_test_mul}/edit",
 *     "delete-form" = "/entity_test/delete/entity_test_mul/{entity_test_mul}",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul.admin_form",
 * )
 */
class EntityTestMul extends EntityTest {

}
