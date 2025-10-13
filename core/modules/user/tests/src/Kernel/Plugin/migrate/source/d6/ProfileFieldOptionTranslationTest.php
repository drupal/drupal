<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\user\Kernel\Plugin\migrate\source\ProfileFieldTest;
use Drupal\user\Plugin\migrate\source\d6\ProfileFieldOptionTranslation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore objectid
/**
 * Tests the field option translation source plugin.
 */
#[CoversClass(ProfileFieldOptionTranslation::class)]
#[Group('migrate_drupal')]
#[RunTestsInSeparateProcesses]
class ProfileFieldOptionTranslationTest extends ProfileFieldTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {

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
