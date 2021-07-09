<?php

namespace Drupal\entity_test\Entity;

/**
 * Defines the multilingual test entity class with bundles.
 *
 * @ContentEntityType(
 *   id = "entity_test_mul_with_bundle",
 *   label = @Translation("Test entity multilingual with bundle - data table"),
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
 *   base_table = "entity_test_mul_with_bundle",
 *   data_table = "entity_test_mul_with_bundle_property_data",
 *   admin_permission = "administer entity_test content",
 *   translatable = TRUE,
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "entity_test_mul_bundle",
 *   links = {
 *     "add-page" = "/entity_test_mul_with_bundle/add",
 *     "add-form" = "/entity_test_mul_with_bundle/add/{type}",
 *     "canonical" = "/entity_test_mul_with_bundle/manage/{entity_test_mul}",
 *     "edit-form" = "/entity_test_mul_with_bundle/manage/{entity_test_mul}/edit",
 *     "delete-form" = "/entity_test/delete/entity_test_mul_with_bundle/{entity_test_mul}",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul_with_bundle.admin_form",
 * )
 */
class EntityTestMulWithBundle extends EntityTest {

}
