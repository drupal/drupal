<?php

namespace Drupal\Tests\system\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Theme settings variables to configuration.
 *
 * @group system
 */
class MigrateThemeSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install bartik and seven themes.
    \Drupal::service('theme_installer')->install(['bartik', 'seven']);
    $this->executeMigration('d7_theme_settings');
  }

  /**
   * Tests migration of theme settings to variables to configuration.
   */
  public function testMigrateThemeSettings() {
    $config = $this->config('bartik.settings');

    $this->assertSame('', $config->get('favicon.path'));
    $this->assertTrue($config->get('favicon.use_default'));
    $this->assertTrue($config->get('features.comment_user_picture'));
    $this->assertTrue($config->get('features.comment_user_verification'));
    $this->assertTrue($config->get('features.favicon'));
    $this->assertTrue($config->get('features.node_user_picture'));
    $this->assertNull($config->get('features.logo'));
    $this->assertTrue($config->get('features.name'));
    $this->assertTrue($config->get('features.slogan'));
    $this->assertSame('public://gnu.png', $config->get('logo.path'));
    $this->assertFalse($config->get('logo.use_default'));

    $config = $this->config('seven.settings');
    $this->assertSame('', $config->get('favicon.path'));
    $this->assertTrue($config->get('favicon.use_default'));
    $this->assertFalse($config->get('features.comment_user_picture'));
    $this->assertTrue($config->get('features.comment_user_verification'));
    $this->assertTrue($config->get('features.favicon'));
    $this->assertTrue($config->get('features.node_user_picture'));
    $this->assertNull($config->get('features.logo'));
    $this->assertTrue($config->get('features.name'));
    $this->assertTrue($config->get('features.slogan'));
    $this->assertSame('', $config->get('logo.path'));
    $this->assertTrue($config->get('logo.use_default'));
  }

}
