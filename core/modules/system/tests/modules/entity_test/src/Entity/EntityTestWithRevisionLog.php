<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\RevisionableContentEntityBase;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_revlog",
 *   label = @Translation("Test entity - revisions log"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_revlog",
 *   revision_table = "entity_test_revlog_revision",
 *   admin_permission = "administer entity_test content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_revlog/manage/{entity_test_revlog}",
 *     "delete-form" = "/entity_test/delete/entity_test_revlog/{entity_test_revlog}",
 *     "edit-form" = "/entity_test_revlog/manage/{entity_test_revlog}/edit",
 *     "revision" = "/entity_test_revlog/{entity_test_revlog}/revision/{entity_test_revlog_revision}/view",
 *   }
 * )
 */
class EntityTestWithRevisionLog extends RevisionableContentEntityBase {

}
