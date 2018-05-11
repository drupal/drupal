<?php

namespace Drupal\Tests\config_translation\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the i18nProfileField source plugin.
 *
 * @covers \Drupal\config_translation\Plugin\migrate\source\d6\ProfileFieldTranslation
 * @group migrate_drupal
 */
class ProfileFieldTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_translation', 'migrate_drupal', 'user'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $test = [];
    $test[0]['source_data'] = [
      'profile_fields' => [
        [
          'fid' => 42,
          'title' => 'I love migrations',
          'name' => 'profile_love_migrations',
        ],
      ],
      'i18n_strings' => [
        [
          'lid' => 10,
          'objectid' => 'profile_love_migrations',
          'type' => 'field',
          'property' => 'title',
        ],
        [
          'lid' => 11,
          'objectid' => 'profile_love_migrations',
          'type' => 'field',
          'property' => 'explanation',
        ],
      ],
      'locales_target' => [
        [
          'lid' => 10,
          'translation' => "J'aime les migrations.",
          'language' => 'fr',
        ],
        [
          'lid' => 11,
          'translation' => 'Si vous cochez cette case, vous aimez les migrations.',
          'language' => 'fr',
        ],
      ],
    ];
    $test[0]['expected_results'] = [
      [
        'property' => 'title',
        'translation' => "J'aime les migrations.",
        'language' => 'fr',
        'fid' => '42',
        'name' => 'profile_love_migrations',
      ],
      [
        'property' => 'explanation',
        'translation' => 'Si vous cochez cette case, vous aimez les migrations.',
        'language' => 'fr',
        'fid' => '42',
        'name' => 'profile_love_migrations',
      ],
    ];
    return $test;
  }

}
