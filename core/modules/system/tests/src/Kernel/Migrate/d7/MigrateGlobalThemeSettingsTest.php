<?php

namespace Drupal\Tests\system\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of global theme settings variables to configuration.
 *
 * @group system
 */
class MigrateGlobalThemeSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_global_theme_settings');
  }

  /**
   * Tests migration of global theme settings to configuration.
   */
  public function testMigrateThemeSettings() {
    $config = $this->config('system.theme.global');

    $this->assertSame('image/png', $config->get('favicon.mimetype'));
    $this->assertSame('public://somefavicon.png', $config->get('favicon.path'));
    $this->assertFalse($config->get('favicon.use_default'));

    $this->assertFalse($config->get('features.comment_user_picture'));
    $this->assertFalse($config->get('features.comment_user_verification'));
    $this->assertFalse($config->get('features.favicon'));
    $this->assertFalse($config->get('features.node_user_picture'));
    $this->assertFalse($config->get('features.logo'));
    $this->assertTrue($config->get('features.name'));
    $this->assertFalse($config->get('features.slogan'));

    $this->assertSame('public://customlogo.png', $config->get('logo.path'));
    $this->assertTrue($config->get('logo.use_default'));
  }

}
