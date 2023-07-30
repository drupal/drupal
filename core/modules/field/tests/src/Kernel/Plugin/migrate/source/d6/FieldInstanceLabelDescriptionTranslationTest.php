<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore objectid

/**
 * Tests the field label and description translation source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d6\FieldLabelDescriptionTranslation
 * @group migrate_drupal
 */
class FieldInstanceLabelDescriptionTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'migrate_drupal',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $test = [];

    // The source data.
    $test[0]['source_data'] = [
      'i18n_strings' => [
        [
          'lid' => 10,
          'objectid' => 'story-field_test_two',
          'type' => 'field',
          'property' => 'widget_label',
        ],
        [
          'lid' => 11,
          'objectid' => 'story-field_test_two',
          'type' => 'field',
          'property' => 'widget_description',
        ],
        [
          'lid' => 12,
          'objectid' => 'story-field_test_two',
          'type' => 'field',
          'property' => 'widget_description',
        ],
      ],
      'locales_target' => [
        [
          'lid' => 10,
          'translation' => "fr - Integer Field",
          'language' => 'fr',
        ],
        [
          'lid' => 11,
          'translation' => 'fr - An example integer field.',
          'language' => 'fr',
        ],
      ],
    ];

    $test[0]['expected_results'] = [
      [
        'property' => 'widget_label',
        'translation' => "fr - Integer Field",
        'language' => 'fr',
        'lid' => '10',
      ],
      [
        'property' => 'widget_description',
        'translation' => 'fr - An example integer field.',
        'language' => 'fr',
        'lid' => '11',
      ],
    ];
    return $test;
  }

}
