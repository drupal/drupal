<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests field option translations migration.
 *
 * @group migrate_drupal_6
 */
class MigrateProfileFieldOptionTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'language',
    'locale',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'user_profile_field',
      'd6_profile_field_option_translation',
    ]);
  }

  /**
   * Tests the Drupal 6 field option translation.
   */
  public function testFieldOptionTranslation() {
    $language_manager = $this->container->get('language_manager');

    /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.user.profile_count_trees');
    $allowed_values = [
      0 => [
        'label' => 'fr - 10',
      ],
      1 => [
        'label' => 'fr - 20',
      ],
      2 => [
        'label' => 'fr - 50',
      ],
      3 => [
        'label' => 'fr - 100',
      ],
      4 => [
        'label' => 'fr - 1000',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.user.profile_sold_to');
    $allowed_values = [
      [
        'label' => 'fr - Pill spammers Fitness spammers Back\slash Forward/slash Dot.in.the.middle',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('zu', 'field.storage.user.profile_sold_to');
    $allowed_values = [
      [
        'label' => 'zu - Pill spammers Fitness spammers Back\slash Forward/slash Dot.in.the.middle',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));
  }

}
