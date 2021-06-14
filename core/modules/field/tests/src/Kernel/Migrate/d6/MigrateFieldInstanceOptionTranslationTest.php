<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate field instance option translations.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldInstanceOptionTranslationTest extends MigrateDrupal6TestBase {

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
    $this->installConfig(['node']);
    $this->executeMigrations([
      'language',
      'd6_node_type',
      'd6_field',
      'd6_field_instance',
      'd6_field_option_translation',
      'd6_field_instance_option_translation',
    ]);
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFieldInstanceOptionTranslation() {
    $language_manager = $this->container->get('language_manager');

    /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_float_single_checkbox');
    $option_translation = ['on_label' => 'fr - 1.234'];
    $this->assertSame($option_translation, $config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('zu', 'field.field.node.story.field_test_float_single_checkbox');
    $option_translation = ['on_label' => 'zu - 1.234'];
    $this->assertSame($option_translation, $config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_text_single_checkbox');
    $option_translation = [
      'off_label' => 'fr - Hello',
      'on_label' => 'fr - Goodbye',
    ];
    $this->assertSame($option_translation, $config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_text_single_checkbox2');
    $option_translation = [
      'off_label' => 'fr - Off',
      'on_label' => 'fr - Hello',
    ];
    $this->assertSame($option_translation, $config_translation->get('settings'));

    $config_translation = $language_manager->getLanguageConfigOverride('zu', 'field.field.node.story.field_test_text_single_checkbox2');
    $option_translation = ['on_label' => 'zu - Hello'];
    $this->assertSame($option_translation, $config_translation->get('settings'));
  }

}
