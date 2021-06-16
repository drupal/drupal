<?php

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'shape' field type.
 *
 * @FieldType(
 *   id = "shape",
 *   label = @Translation("Shape"),
 *   description = @Translation("Another dummy field type."),
 * )
 */
class ShapeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'foreign_key_name' => 'shape',
    ] + parent::defaultStorageSettings();
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
    $foreign_keys = [];
    // The 'foreign keys' key is not always used in tests.
    if ($field_definition->getSetting('foreign_key_name')) {
      $foreign_keys['foreign keys'] = [
        // This is a dummy foreign key definition, references a table that
        // doesn't exist, but that's not a problem.
        $field_definition->getSetting('foreign_key_name') => [
          'table' => $field_definition->getSetting('foreign_key_name'),
          'columns' => [$field_definition->getSetting('foreign_key_name') => 'id'],
        ],
      ];
    }
    return [
      'columns' => [
        'shape' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'color' => [
          'type' => 'varchar',
          'length' => 32,
        ],
      ],
    ] + $foreign_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $item = $this->getValue();
    return empty($item['shape']) && empty($item['color']);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'shape';
  }

}
