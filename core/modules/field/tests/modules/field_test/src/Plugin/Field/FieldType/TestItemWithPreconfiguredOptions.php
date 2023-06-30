<?php

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'test_field_with_preconfigured_options' entity field item.
 *
 * @FieldType(
 *   id = "test_field_with_preconfigured_options",
 *   label = @Translation("Test field with preconfigured options"),
 *   description = @Translation("Dummy field type used for tests."),
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class TestItemWithPreconfiguredOptions extends TestItem implements PreconfiguredFieldUiOptionsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [
      'custom_options' => [
        'label' => new TranslatableMarkup('All custom options'),
        'field_storage_config' => [
          'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
          'settings' => [
            'test_field_storage_setting' => 'preconfigured_storage_setting',
          ],
        ],
        'field_config' => [
          'required' => TRUE,
          'settings' => [
            'test_field_setting' => 'preconfigured_field_setting',
          ],
        ],
        'entity_form_display' => [
          'type' => 'test_field_widget_multiple',
        ],
        'entity_view_display' => [
          'type' => 'field_test_multiple',
        ],
      ],
    ];
  }

}
