<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mulrevpub",
 *   label = @Translation("Test entity - revisions, data table, and published interface"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_mulrevpub",
 *   data_table = "entity_test_mulrevpub_property_data",
 *   revision_table = "entity_test_mulrevpub_revision",
 *   revision_data_table = "entity_test_mulrevpub_property_revision",
 *   admin_permission = "administer entity_test content",
 *   translatable = TRUE,
 *   show_revision_ui = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "add-form" = "/entity_test_mulrevpub/add",
 *     "canonical" = "/entity_test_mulrevpub/manage/{entity_test_mulrevpub}",
 *     "delete-form" = "/entity_test/delete/entity_test_mulrevpub/{entity_test_mulrevpub}",
 *     "delete-multiple-form" = "/entity_test/delete",
 *     "edit-form" = "/entity_test_mulrevpub/manage/{entity_test_mulrevpub}/edit",
 *     "revision" = "/entity_test_mulrevpub/{entity_test_mulrevpub}/revision/{entity_test_mulrevpub_revision}/view",
 *   }
 * )
 */
class EntityTestMulRevPub extends EntityTestMulRev implements EntityPublishedInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    return parent::baseFieldDefinitions($entity_type) + EntityPublishedTrait::publishedBaseFieldDefinitions($entity_type);
  }

}
