<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mulrev_changed",
 *   label = @Translation("Test entity - revisions and data table"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "entity_test_mulrev_changed",
 *   data_table = "entity_test_mulrev_changed_property",
 *   revision_table = "entity_test_mulrev_changed_revision",
 *   revision_data_table = "entity_test_mulrev_changed_property_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-form" = "/entity_test_mulrev_changed/add",
 *     "canonical" = "/entity_test_mulrev_changed/manage/{entity_test_mulrev_changed}",
 *     "delete-form" = "/entity_test/delete/entity_test_mulrev_changed/{entity_test_mulrev_changed}",
 *     "edit-form" = "/entity_test_mulrev_changed/manage/{entity_test_mulrev_changed}/edit",
 *     "revision" = "/entity_test_mulrev_changed/{entity_test_mulrev_changed}/revision/{entity_test_mulrev_changed_revision}/view",
 *   }
 * )
 */
class EntityTestMulRevChanged extends EntityTestMulChanged {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The version id of the test entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['langcode']->setRevisionable(TRUE);
    $fields['name']->setRevisionable(TRUE);
    $fields['user_id']->setRevisionable(TRUE);
    $fields['changed']->setRevisionable(TRUE);
    $fields['not_translatable']->setRevisionable(TRUE);

    return $fields;
  }

}
