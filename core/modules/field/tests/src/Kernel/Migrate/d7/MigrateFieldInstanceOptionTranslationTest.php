<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrate field instance option translations.
 *
 * @group migrate_drupal_7
 */
class MigrateFieldInstanceOptionTranslationTest extends MigrateDrupal7TestBase {

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
    $this->installConfig(['node']);
    $this->executeMigration('language');
    $this->migrateFields();
    $this->executeMigrations([
      'd7_field_option_translation',
      'd7_field_instance_option_translation',
    ]);
  }

  /**
   * Migrate field instance option translations.
   */
  public function testFieldInstanceOptionTranslation(): void {
    $language_manager = $this->container->get('language_manager');

    /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.blog.field_boolean');
    $this->assertNull($config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.field.node.blog.field_boolean');
    $option_translation = [
      'off_label' => 'is - Off',
      'on_label' => 'is - 1',
    ];

    $this->assertSame($option_translation, $config_translation->get('settings'));
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.test_content_type.field_boolean');
    $this->assertNull($config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.field.node.test_content_type.field_boolean');
    $this->assertSame($option_translation, $config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.article.field_checkbox');
    $option_translation = [
      'off_label' => 'fr - Stop',
      'on_label' => 'Go',
    ];
    $this->assertSame($option_translation, $config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.field.node.article.field_checkbox');
    $option_translation = [
      'off_label' => 'is - Stop',
      'on_label' => 'is - Go',
    ];
    $this->assertSame($option_translation, $config_translation->get('settings'));
  }

}
