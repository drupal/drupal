<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\user\Kernel\Plugin\migrate\source\ProfileFieldTest;

/**
 * Tests the field option translation source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d6\ProfileFieldOptionTranslation
 * @group migrate_drupal
 */
class ProfileFieldOptionTranslationTest extends ProfileFieldTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {

    $test = parent::providerSource();
    // The source data.
    $test[0]['source_data']['i18n_strings'] = [
      [
        'lid' => 10,
        'objectid' => 'profile_color',
        'type' => 'field',
        'property' => 'options',
      ],
      [
        'lid' => 1,
        'objectid' => 'profile_last_name',
        'type' => 'field',
        'property' => 'options',
      ],
    ];
    $test[0]['source_data']['locales_target'] = [
      [
        'lid' => 10,
        'translation' => "fr - red\nfr - blue\nfr - green",
        'language' => 'fr',
      ],
    ];

    $test[0]['expected_data'] = [
      [
        'fid' => 4,
        'title' => 'Color',
        'name' => 'profile_color',
        'explanation' => 'A selection that allows user to select a color',
        'category' => 'profile',
        'page' => '',
        'type' => 'selection',
        'weight' => 0,
        'required' => 0,
        'register' => 0,
        'visibility' => 2,
        'autocomplete' => 0,
        'options' => [
          'red' => 'red',
          'blue' => 'blue',
          'green' => 'green',
          'yellow' => 'yellow',
        ],
        'property' => 'options',
        'objectid' => 'profile_color',
        'translation' => "fr - red\nfr - blue\nfr - green",
        'language' => 'fr',
      ],
    ];
    return $test;
  }

}
