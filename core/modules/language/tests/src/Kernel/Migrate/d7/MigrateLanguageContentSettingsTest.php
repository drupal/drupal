<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of language content setting variables,
 * language_content_type_$type, i18n_node_options_* and i18n_lock_node_*.
 *
 * @group migrate_drupal_7
 */
class MigrateLanguageContentSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
    'language',
    'content_translation',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->migrateContentTypes();
    $this->executeMigration('d7_language_content_settings');
  }

  /**
   * Tests migration of content language settings.
   */
  public function testLanguageContent() {
    // Assert that a translatable content is still translatable.
    $config = $this->config('language.content_settings.node.blog');
    $this->assertSame($config->get('target_entity_type_id'), 'node');
    $this->assertSame($config->get('target_bundle'), 'blog');
    $this->assertSame($config->get('default_langcode'), 'current_interface');
    $this->assertFalse($config->get('language_alterable'));
    $this->assertTrue($config->get('third_party_settings.content_translation.enabled'));

    // Assert that a translatable content is translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'page');
    $this->assertFalse($config->isDefaultConfiguration());
    $this->assertTrue($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'current_interface');

    // Assert that a non-translatable content is not translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'forum');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    // Make sure there's no migration exceptions.
    $messages = $this->migration->getIdMap()->getMessages()->fetchAll();
    $this->assertEmpty($messages);

    // Assert that a content type translatable with entity_translation is still
    // translatable.
    $config = $this->config('language.content_settings.node.test_content_type');
    $this->assertTrue($config->get('third_party_settings.content_translation.enabled'));
    $this->assertSame($config->get('default_langcode'), 'und');

    // Assert that a content type without a 'language_content_type' variable is
    // not translatable
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'book');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

  }

}
