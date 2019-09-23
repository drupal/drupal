<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_rev",
 *   label = @Translation("Test entity - revisions"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm"
 *     },
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_rev",
 *   revision_table = "entity_test_rev_revision",
 *   admin_permission = "administer entity_test content",
 *   show_revision_ui = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-form" = "/entity_test_rev/add",
 *     "canonical" = "/entity_test_rev/manage/{entity_test_rev}",
 *     "delete-form" = "/entity_test/delete/entity_test_rev/{entity_test_rev}",
 *     "delete-multiple-form" = "/entity_test_rev/delete_multiple",
 *     "edit-form" = "/entity_test_rev/manage/{entity_test_rev}/edit",
 *     "revision" = "/entity_test_rev/{entity_test_rev}/revision/{entity_test_rev_revision}/view",
 *   }
 * )
 */
class EntityTestRev extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name']->setRevisionable(TRUE);
    $fields['user_id']->setRevisionable(TRUE);

    $fields['non_rev_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non Revisionable Field'))
      ->setDescription(t('A non-revisionable test field.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    return $fields;
  }

}
