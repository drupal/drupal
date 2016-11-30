<?php

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'test_object_field' entity field item.
 *
 * @FieldType(
 *   id = "test_object_field",
 *   label = @Translation("Test object field"),
 *   description = @Translation("Test field type that has an object to test serialization"),
 *   default_widget = "test_object_field_widget",
 *   default_formatter = "object_field_test_default"
 * )
 */
class TestObjectItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The object item value.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values['value'])) {
      // @todo Remove this in https://www.drupal.org/node/2788637.
      if (is_string($values['value'])) {
        $values['value'] = unserialize($values['value']);
      }
    }
    parent::setValue($values, $notify);
  }

}
