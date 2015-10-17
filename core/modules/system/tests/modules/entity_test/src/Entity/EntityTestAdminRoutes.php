<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestAdminRoutes.
 */

namespace Drupal\entity_test\Entity;

/**
 * Defines a test entity type with administrative routes.
 *
 * @ContentEntityType(
 *   id = "entity_test_admin_routes",
 *   label = @Translation("Test entity - admin routes"),
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
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_admin_routes",
 *   data_table = "entity_test_admin_routes_property_data",
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
 *     "canonical" = "/entity_test_admin_routes/manage/{entity_test_admin_routes}",
 *     "edit-form" = "/entity_test_admin_routes/manage/{entity_test_admin_routes}/edit",
 *     "delete-form" = "/entity_test/delete/entity_test_admin_routes/{entity_test_admin_routes}",
 *   },
 * )
 */
class EntityTestAdminRoutes extends EntityTest {

}
