<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestStringId.
 */

namespace Drupal\entity_test\Entity;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test entity class with a string ID.
 *
 * @ContentEntityType(
 *   id = "entity_test_string_id",
 *   label = @Translation("Test entity with string_id"),
 *   controllers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test_string",
 *   fieldable = TRUE,
 *   field_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "entity_test.render",
 *     "edit-form" = "entity_test.edit_entity_test_string_id",
 *     "admin-form" = "entity_test.admin_entity_test_string_id"
 *   }
 * )
 */
class EntityTestStringId extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = FieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the test entity.'))
      ->setReadOnly(TRUE);
    return $fields;
  }

}
