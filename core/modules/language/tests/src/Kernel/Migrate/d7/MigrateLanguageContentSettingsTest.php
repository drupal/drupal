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
  public static $modules = ['node', 'text', 'language', 'content_translation', 'menu_ui'];
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['node']);
    $this->executeMigrations(['d7_node_type', 'd7_language_content_settings']);
  }

  /**
   * Tests migration of content language settings.
   */
  public function testLanguageContent() {
    // Assert that a translatable content is still translatable.
    $config = $this->config('language.content_settings.node.blog');
    $this->assertIdentical($config->get('target_entity_type_id'), 'node');
    $this->assertIdentical($config->get('target_bundle'), 'blog');
    $this->assertIdentical($config->get('default_langcode'), 'current_interface');
    $this->assertFalse($config->get('language_alterable'));
    $this->assertTrue($config->get('third_party_settings.content_translation.enabled'));

    // Assert that a non-translatable content is not translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'page');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

  }

}
