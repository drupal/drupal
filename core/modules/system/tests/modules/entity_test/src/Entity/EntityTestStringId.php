<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestStringId.
 */

namespace Drupal\entity_test\Entity;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test entity class with a string ID.
 *
 * @ContentEntityType(
 *   id = "entity_test_string_id",
 *   label = @Translation("Test entity with string_id"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test_string",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/entity_test_string_id/manage/{entity_test_string_id}",
 *     "edit-form" = "/entity_test_string_id/manage/{entity_test_string_id}",
 *   },
 *   field_ui_base_route = "entity.entity_test_string_id.admin_form",
 * )
 */
class EntityTestStringId extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the test entity.'))
      ->setReadOnly(TRUE);
    return $fields;
  }

}
