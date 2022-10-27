<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_revpub",
 *   label = @Translation("Test entity - revisions and publishing status"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm",
 *       "delete-multiple-confirm" = \Drupal\Core\Entity\Form\DeleteMultipleForm::class,
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider::class,
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "entity_test_revpub",
 *   revision_table = "entity_test_revpub_revision",
 *   admin_permission = "administer entity_test content",
 *   show_revision_ui = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "add-form" = "/entity_test_revpub/add",
 *     "canonical" = "/entity_test_revpub/manage/{entity_test_revpub}",
 *     "delete-form" = "/entity_test/delete/entity_test_revpub/{entity_test_revpub}",
 *     "delete-multiple-form" = "/entity_test_revpub/delete_multiple",
 *     "edit-form" = "/entity_test_revpub/manage/{entity_test_revpub}/edit",
 *     "revision" = "/entity_test_revpub/{entity_test_revpub}/revision/{entity_test_revpub_revision}/view",
 *     "revision-delete-form" = "/entity_test_revpub/{entity_test_revpub}/revision/{entity_test_revpub_revision}/delete",
 *     "revision-revert-form" = "/entity_test_revpub/{entity_test_revpub}/revision/{entity_test_revpub_revision}/revert",
 *     "version-history" = "/entity_test_revpub/{entity_test_revpub}/revisions",
 *   }
 * )
 */
class EntityTestRevPub extends EntityTestRev implements EntityPublishedInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the publishing status field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    return $fields;
  }

}
