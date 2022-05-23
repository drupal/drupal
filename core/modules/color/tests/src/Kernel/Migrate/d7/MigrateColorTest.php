<?php

namespace Drupal\Tests\color\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Color variables to configuration.
 *
 * @group color
 * @group legacy
 */
class MigrateColorTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['color'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install the themes used for this test.
    $this->container->get('theme_installer')->install(['bartik']);
    $this->executeMigration('d7_color');
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal7.php';
  }

  /**
   * Tests migration of color's variables to configuration.
   */
  public function testMigrateColor() {
    // Test Bartik migration.
    $config = $this->config('color.theme.bartik');
    $files = [
      'public://color/bartik-e0e23ad7/logo.png',
      'public://color/bartik-e0e23ad7/colors.css',
    ];
    $this->assertSame($files, $config->get('files'));
    $this->assertSame('public://color/bartik-e0e23ad7/logo.png', $config->get('logo'));
    $palette = [
      'top' => '#d0d0d0',
      'bottom' => '#c2c4c5',
      'bg' => '#ffffff',
      'sidebar' => '#ffffff',
      'sidebarborders' => '#cccccc',
      'footer' => '#24272c',
      'titleslogan' => '#000000',
      'text' => '#4a4a4a',
      'link' => '#019dbf',
    ];
    $this->assertSame($palette, $config->get('palette'));
    $this->assertSame(['public://color/bartik-e0e23ad7/colors.css'], $config->get('stylesheets'));
    // Test that the screenshot was not migrated.
    $this->assertNull($config->get('screenshot'));

    // Test that garland was not migrated.
    $this->assertEmpty(\Drupal::config('color.theme.garland')->get());
  }

}
