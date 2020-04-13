<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate field option translations.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldOptionTranslationTest extends MigrateDrupal6TestBase {

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
      'd6_field',
      'd6_field_option_translation',
    ]);
  }

  /**
   * Tests the Drupal 6 field to Drupal 8 migration.
   */
  public function testFieldOptionTranslation() {
    $language_manager = $this->container->get('language_manager');

    // Test a select list with allowed values of key only.
    /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.node.field_test_integer_selectlist');
    $allowed_values = [
      1 => [
        'label' => 'fr - 2341',
      ],
      3 => [
        'label' => 'fr - 4123',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('zu', 'field.storage.node.field_test_integer_selectlist');
    $allowed_values = [
      1 => [
        'label' => 'zu - 2341',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    // Test a select list with allowed values of key|label.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.node.field_test_string_selectlist');
    $allowed_values = [
      0 => [
        'label' => 'Noir',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('zu', 'field.storage.node.field_test_string_selectlist');
    $allowed_values = [
      0 => [
        'label' => 'Okumnyama',
      ],
      1 => [
        'label' => 'Mhlophe',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));
  }

}
