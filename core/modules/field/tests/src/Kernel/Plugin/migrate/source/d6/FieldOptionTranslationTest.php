<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore optionwidgets selectlist objectid objectindex plid

/**
 * Tests the field option translation source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d6\FieldOptionTranslation
 * @group migrate_drupal
 */
class FieldOptionTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $test = [];

    // The source data.
    $test[0]['source_data']['content_node_field'] = [
      [
        'field_name' => 'field_test_text_single_checkbox',
        'type' => 'text',
        'global_settings' => 'a:4:{s:15:"text_processing";s:1:"0";s:10:"max_length";s:0:"";s:14:"allowed_values";s:10:"Off\\nHello";s:18:"allowed_values_php";s:0:"";}',
        'required' => 0,
        'multiple' => 0,
        'db_storage' => 1,
        'module' => 'text',
      ],
      [
        'field_name' => 'field_test_integer_selectlist',
        'type' => 'number_integer',
        'global_settings' => 'a:6:{s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:14:"allowed_values";s:22:"1234\\n2341\\n3412\\n4123";s:18:"allowed_values_php";s:0:"";}',
        'required' => 0,
        'multiple' => 0,
        'db_storage' => 1,
        'module' => 'text',
      ],
    ];
    $test[0]['source_data']['content_node_field_instance'] = [
      [
        'field_name' => 'field_test_text_single_checkbox',
        'type_name' => 'story',
        'weight' => 1,
        'label' => 'Text Single Checkbox Field',
        'widget_type' => 'optionwidgets_onoff',
        'description' => 'An example text field using a single on/off checkbox.',
        'widget_module' => 'optionwidgets',
        'widget_active' => 1,
        'required' => 1,
        'active' => 1,
        'global_settings' => 'a:0;',
        'widget_settings' => 'a:0;',
        'display_settings' => 'a:0;',
      ],
      [
        'field_name' => 'field_test_integer_selectlist',
        'type_name' => 'story',
        'weight' => 1,
        'label' => 'Integer Select List Field',
        'widget_type' => 'optionwidgets_select',
        'description' => 'An example integer field using a select list.',
        'widget_module' => 'optionwidgets',
        'widget_active' => 1,
        'required' => 1,
        'active' => 1,
        'global_settings' => 'a:0;',
        'widget_settings' => 'a:0;',
        'display_settings' => 'a:0;',
      ],
    ];

    $test[0]['source_data']['i18n_strings'] = [
      [
        'lid' => 10,
        'objectid' => 'field_test_text_single_checkbox',
        'type' => 'field',
        'property' => 'option_0',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 11,
        'objectid' => 'field_test_text_single_checkbox',
        'type' => 'field',
        'property' => 'option_1',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 20,
        'objectid' => 'field_test_integer_selectlist',
        'type' => 'field',
        'property' => 'option_1234',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 21,
        'objectid' => 'field_test_integer_selectlist',
        'type' => 'field',
        'property' => 'option_4123',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 22,
        'objectid' => 'field_test_integer_selectlist',
        'type' => 'field',
        'property' => 'option_0',
        'objectindex' => 0,
        'format' => 0,
      ],
    ];
    $test[0]['source_data']['locales_target'] = [
      [
        'lid' => 10,
        'translation' => "fr - Hello",
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 11,
        'translation' => 'fr - Goodbye',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 20,
        'translation' => "fr - 4444",
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 21,
        'translation' => 'fr - 5555',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    $test[0]['expected_data'] = [
      [
        'field_name' => 'field_test_text_single_checkbox',
        'type' => 'text',
        'widget_type' => 'optionwidgets_onoff',
        'global_settings' => [
          'allowed_values' => 'Off\nHello',
          'allowed_values_php' => '',
          'max_length' => '',
          'text_processing' => '0',
        ],
        'db_columns' => '',
        'property' => 'option_0',
        'objectid' => 'field_test_text_single_checkbox',
        'language' => 'fr',
        'translation' => 'fr - Hello',
        'objectindex' => 0,
        'format' => 0,
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'field_name' => 'field_test_text_single_checkbox',
        'type' => 'text',
        'widget_type' => 'optionwidgets_onoff',
        'global_settings' => [
          'allowed_values' => 'Off\nHello',
          'allowed_values_php' => '',
          'max_length' => '',
          'text_processing' => '0',
        ],
        'db_columns' => '',
        'property' => 'option_1',
        'objectid' => 'field_test_text_single_checkbox',
        'language' => 'fr',
        'translation' => 'fr - Goodbye',
        'objectindex' => 0,
        'format' => 0,
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'field_name' => 'field_test_integer_selectlist',
        'type' => 'number_integer',
        'widget_type' => 'optionwidgets_select',
        'global_settings' => [
          'allowed_values' => '1234\n2341\n3412\n4123',
          'max' => '',
          'min' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values_php' => '',
        ],
        'db_columns' => '',
        'property' => 'option_1234',
        'objectid' => 'field_test_integer_selectlist',
        'language' => 'fr',
        'translation' => 'fr - 4444',
        'objectindex' => 0,
        'format' => 0,
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'field_name' => 'field_test_integer_selectlist',
        'type' => 'number_integer',
        'widget_type' => 'optionwidgets_select',
        'global_settings' => [
          'allowed_values' => '1234\n2341\n3412\n4123',
          'max' => '',
          'min' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values_php' => '',
        ],
        'db_columns' => '',
        'property' => 'option_4123',
        'objectid' => 'field_test_integer_selectlist',
        'language' => 'fr',
        'translation' => 'fr - 5555',
        'objectindex' => 0,
        'format' => 0,
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    // Change the name of the locale_target i18n status field.
    $test[1] = $test[0];
    foreach ($test[1]['source_data']['locales_target'] as &$lt) {
      $lt['status'] = $lt['i18n_status'];
      unset($lt['i18n_status']);
    }

    return $test;
  }

}
