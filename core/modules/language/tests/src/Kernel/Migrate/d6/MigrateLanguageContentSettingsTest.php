<?php

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of language content setting variables,
 * language_content_type_$type, i18n_node_options_* and i18n_lock_node_*.
 *
 * @group migrate_drupal_6
 */
class MigrateLanguageContentSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'text', 'language', 'content_translation', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['node']);
    $this->executeMigrations(['d6_node_type', 'd6_language_content_settings']);
  }

  /**
   * Tests migration of content language settings.
   */
  public function testLanguageContent() {
    // Assert that a translatable content is still translatable.
    $config = $this->config('language.content_settings.node.article');
    $this->assertSame($config->get('target_entity_type_id'), 'node');
    $this->assertSame($config->get('target_bundle'), 'article');
    $this->assertSame($config->get('default_langcode'), 'current_interface');
    $this->assertTrue($config->get('third_party_settings.content_translation.enabled'));

    // Assert that a non-translatable content is not translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'company');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');
  }

  /**
   * Tests migration of content language settings when there is no language lock.
   */
  public function testLanguageContentWithNoLanguageLock() {
    // Assert that a we can assign a language.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'employee');
    $this->assertSame($config->getDefaultLangcode(), 'current_interface');
    $this->assertTrue($config->isLanguageAlterable());
  }

}
