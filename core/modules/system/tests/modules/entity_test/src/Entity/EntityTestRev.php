<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestRev.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_rev",
 *   label = @Translation("Test entity - revisions"),
 *   controllers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test_rev",
 *   revision_table = "entity_test_rev_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "entity_test.edit_entity_test_rev",
 *     "delete-form" = "entity_test.delete_entity_test_rev",
 *     "edit-form" = "entity_test.edit_entity_test_rev"
 *   }
 * )
 */
class EntityTestRev extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['revision_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The version id of the test entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode']->setRevisionable(TRUE);
    $fields['name']->setRevisionable(TRUE);
    $fields['user_id']->setRevisionable(TRUE);

    return $fields;
  }

}
