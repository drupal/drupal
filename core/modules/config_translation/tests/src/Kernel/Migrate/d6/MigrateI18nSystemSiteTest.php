<?php

namespace Drupal\Tests\config_translation\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade i18n_strings site variables to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateI18nSystemSiteTest extends MigrateDrupal6TestBase {

  public static $modules = ['language', 'config_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_i18n_system_site');
  }

  /**
   * Tests migration of system (site) variables to system.site.yml.
   */
  public function testSystemSite() {
    $config_translation = \Drupal::service('language_manager')->getLanguageConfigOverride('fr', 'system.site');
    $this->assertIdentical('fr site name', $config_translation->get('name'));
    $this->assertIdentical('fr_site_mail@example.com', $config_translation->get('mail'));
    $this->assertIdentical('fr Migrate rocks', $config_translation->get('slogan'));
    $this->assertIdentical('/fr-user', $config_translation->get('page.403'));
    $this->assertIdentical('/fr-page-not-found', $config_translation->get('page.404'));
    $this->assertIdentical('/node', $config_translation->get('page.front'));
    $this->assertIdentical(NULL, $config_translation->get('admin_compact_mode'));

    $config_translation = \Drupal::service('language_manager')->getLanguageConfigOverride('zu', 'system.site');
    $this->assertIdentical('zu - site_name', $config_translation->get('name'));
    $this->assertIdentical('site_mail@example.com', $config_translation->get('mail'));
    $this->assertIdentical('Migrate rocks', $config_translation->get('slogan'));
    $this->assertIdentical('/zu-user', $config_translation->get('page.403'));
    $this->assertIdentical('/zu-page-not-found', $config_translation->get('page.404'));
    $this->assertIdentical('/node', $config_translation->get('page.front'));
    $this->assertIdentical(NULL, $config_translation->get('admin_compact_mode'));
  }

}
