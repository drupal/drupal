<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldType\ShapeItem.
 */

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'shape_field' entity field item.
 *
 * @FieldType(
 *   id = "shape",
 *   label = @Translation("Shape"),
 *   description = @Translation("Another dummy field type."),
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class ShapeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'foreign_key_name' => 'shape',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['shape'] = DataDefinition::create('string')
      ->setLabel(t('Shape'));

    $properties['color'] = DataDefinition::create('string')
      ->setLabel(t('Color'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $foreign_keys = array();
    // The 'foreign keys' key is not always used in tests.
    if ($field_definition->getSetting('foreign_key_name')) {
      $foreign_keys['foreign keys'] = array(
        // This is a dummy foreign key definition, references a table that
        // doesn't exist, but that's not a problem.
        $field_definition->getSetting('foreign_key_name') => array(
          'table' => $field_definition->getSetting('foreign_key_name'),
          'columns' => array($field_definition->getSetting('foreign_key_name') => 'id'),
        ),
      );
    }
    return array(
      'columns' => array(
        'shape' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
        'color' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
    ) + $foreign_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $item = $this->getValue();
    return empty($item['shape']) && empty($item['color']);
  }

}
