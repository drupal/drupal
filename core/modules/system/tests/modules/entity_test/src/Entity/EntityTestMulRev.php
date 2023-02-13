<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mulrev",
 *   label = @Translation("Test entity - mul revisions and data table"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_mulrev",
 *   data_table = "entity_test_mulrev_property_data",
 *   revision_table = "entity_test_mulrev_revision",
 *   revision_data_table = "entity_test_mulrev_property_revision",
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
 *   },
 *   links = {
 *     "add-form" = "/entity_test_mulrev/add",
 *     "canonical" = "/entity_test_mulrev/manage/{entity_test_mulrev}",
 *     "delete-form" = "/entity_test/delete/entity_test_mulrev/{entity_test_mulrev}",
 *     "edit-form" = "/entity_test_mulrev/manage/{entity_test_mulrev}/edit",
 *     "revision" = "/entity_test_mulrev/{entity_test_mulrev}/revision/{entity_test_mulrev_revision}/view",
 *   }
 * )
 */
class EntityTestMulRev extends EntityTestRev {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['non_mul_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non translatable'))
      ->setDescription(t('A non-translatable string field'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
