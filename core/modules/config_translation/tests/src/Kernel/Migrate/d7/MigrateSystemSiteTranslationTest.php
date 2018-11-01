<?php

namespace Drupal\Tests\config_translation\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrate multilingual site variables.
 *
 * @group migrate_drupal_7
 */
class MigrateSystemSiteTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'config_translation',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_system_site_translation');
  }

  /**
   * Tests migration of system (site) variables to system.site.yml.
   */
  public function testSystemSite() {
    $language_manager = \Drupal::service('language_manager');
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'system.site');
    $this->assertSame('The Site Name', $config_translation->get('name'));
    $this->assertSame('fr - The Slogan', $config_translation->get('slogan'));
    $this->assertSame('node', $config_translation->get('page.403'));
    $this->assertSame('node', $config_translation->get('page.404'));
    $this->assertSame('node', $config_translation->get('page.front'));
    $this->assertSame(NULL, $config_translation->get('admin_compact_mode'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'system.site');
    $this->assertSame('is - The Site Name', $config_translation->get('name'));
    $this->assertSame('is - The Slogan', $config_translation->get('slogan'));
    $this->assertSame('node/1', $config_translation->get('page.403'));
    $this->assertSame('node/6', $config_translation->get('page.404'));
    $this->assertSame('node/4', $config_translation->get('page.front'));
    $this->assertNULL($config_translation->get('admin_compact_mode'));
  }

}
