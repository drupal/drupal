<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrate field option translations.
 *
 * @group migrate_drupal_7
 */
class MigrateFieldOptionTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'config_translation',
    'datetime',
    'datetime_range',
    'file',
    'image',
    'language',
    'link',
    'locale',
    'menu_ui',
    'node',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'd7_field',
      'd7_field_option_translation',
    ]);
  }

  /**
   * Tests the Drupal 7 field option translation to Drupal 8 migration.
   */
  public function testFieldOptionTranslation(): void {
    $language_manager = $this->container->get('language_manager');

    /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.node.field_color');
    $allowed_values = [
      0 => [
        'label' => 'Verte',
      ],
      1 => [
        'label' => 'Noire',
      ],
      2 => [
        'label' => 'Blanche',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.storage.node.field_color');
    $allowed_values = [
      0 => [
        'label' => 'Grænn',
      ],
      1 => [
        'label' => 'Svartur',
      ],
      2 => [
        'label' => 'Hvítur',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.node.field_rating');
    $allowed_values = [
      0 => [
        'label' => 'Haute',
      ],
      1 => [
        'label' => 'Moyenne',
      ],
      2 => [
        'label' => 'Faible',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.storage.node.field_rating');
    $allowed_values = [
      0 => [
        'label' => 'Hár',
      ],
      1 => [
        'label' => 'Miðlungs',
      ],
      2 => [
        'label' => 'Lágt',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.node.field_boolean');
    $this->assertNull($config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.storage.node.field_boolean');
    $allowed_values = [
      0 => [
        0 => 'Off',
        1 => '1',
      ],
      1 => [
        0 => 'Off',
        1 => '1',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.storage.node.field_checkbox');
    $allowed_values = [
      0 => [
        0 => 'Stop',
        1 => 'Go',
      ],
      1 => [
        0 => 'Stop',
        1 => 'Go',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));
    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.storage.node.field_checkbox');
    $allowed_values = [
      0 => [
        0 => 'Stop',
        1 => 'Go',
      ],
      1 => [
        0 => 'Stop',
        1 => 'Go',
      ],
    ];
    $this->assertSame($allowed_values, $config_translation->get('settings.allowed_values'));
    // Ensure that the count query works as expected.
    $this->assertCount(20, $this->getMigration('d7_field_option_translation')->getSourcePlugin());
  }

}
