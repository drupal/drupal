<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestBaseFieldDisplay.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity class for base fields display.
 *
 * @ContentEntityType(
 *   id = "entity_test_base_field_display",
 *   label = @Translation("Test entity - base field display"),
 *   controllers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "edit-form" = "entity_test.edit_entity_test",
 *   },
 *   field_ui_base_route = "entity_test.admin_entity_test",
 * )
 */
class EntityTestBaseFieldDisplay extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['test_no_display'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Field with no display'));

    $fields['test_display_configurable'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Field with configurable display'))
      ->setDisplayOptions('view', array(
        'type' => 'text_default',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['test_display_non_configurable'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Field with non-configurable display'))
      ->setDisplayOptions('view', array(
        'type' => 'text_default',
        'weight' => 11,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 11,
      ));

    return $fields;
  }

}
