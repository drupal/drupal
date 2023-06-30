<?php

namespace Drupal\image_module_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a dummy field containing an AJAX handler.
 *
 * @FieldType(
 *   id = "image_module_test_dummy_ajax",
 *   label = @Translation("Dummy AJAX"),
 *   description = @Translation("A field containing an AJAX handler."),
 *   default_widget = "image_module_test_dummy_ajax_widget",
 *   default_formatter = "image_module_test_dummy_ajax_formatter"
 * )
 */
class DummyAjaxItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('value')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Dummy string value'));

    return $properties;
  }

}
