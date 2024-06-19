<?php

declare(strict_types=1);

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
  protected static $modules = [
    'language',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'system_site',
      'd7_system_site_translation',
    ]);
  }

  /**
   * Tests migration of system (site) variables to system.site.yml.
   */
  public function testSystemSite(): void {
    $language_manager = \Drupal::service('language_manager');
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'system.site');
    $this->assertSame('The Site Name', $config_translation->get('name'));
    $this->assertSame('fr - The Slogan', $config_translation->get('slogan'));
    $this->assertNull($config_translation->get('page.403'));
    $this->assertNull($config_translation->get('page.404'));
    $this->assertNull($config_translation->get('page.front'));
    $this->assertNull($config_translation->get('admin_compact_mode'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'system.site');
    $this->assertSame('is - The Site Name', $config_translation->get('name'));
    $this->assertSame('is - The Slogan', $config_translation->get('slogan'));
    $this->assertNull($config_translation->get('page.403'));
    $this->assertNull($config_translation->get('page.404'));
    $this->assertNull($config_translation->get('page.front'));
    $this->assertNull($config_translation->get('admin_compact_mode'));
  }

}
