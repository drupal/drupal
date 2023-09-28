<?php

namespace Drupal\entity_test_revlog\Entity;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mul_revlog",
 *   label = @Translation("Test entity - data table, revisions log"),
 *   handlers = {
 *     "access" = \Drupal\entity_test_revlog\EntityTestRevlogAccessControlHandler::class,
 *     "form" = {
 *       "default" = \Drupal\Core\Entity\ContentEntityForm::class,
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider::class,
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "entity_test_mul_revlog",
 *   data_table = "entity_test_mul_revlog_field_data",
 *   revision_table = "entity_test_mul_revlog_revision",
 *   revision_data_table = "entity_test_mul_revlog_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 *   links = {
 *     "add-form" = "/entity_test_mul_revlog/add",
 *     "canonical" = "/entity_test_mul_revlog/manage/{entity_test_mul_revlog}",
 *     "delete-form" = "/entity_test/delete/entity_test_mul_revlog/{entity_test_mul_revlog}",
 *     "edit-form" = "/entity_test_mul_revlog/manage/{entity_test_mul_revlog}/edit",
 *     "revision" = "/entity_test_mul_revlog/{entity_test_mul_revlog}/revision/{entity_test_mul_revlog_revision}/view",
 *     "revision-delete-form" = "/entity_test_mul_revlog/{entity_test_mul_revlog}/revision/{entity_test_mul_revlog_revision}/delete",
 *     "revision-revert-form" = "/entity_test_mul_revlog/{entity_test_mul_revlog}/revision/{entity_test_mul_revlog_revision}/revert",
 *     "version-history" = "/entity_test_mul_revlog/{entity_test_mul_revlog}/revisions",
 *   }
 * )
 */
class EntityTestMulWithRevisionLog extends EntityTestWithRevisionLog {

}
