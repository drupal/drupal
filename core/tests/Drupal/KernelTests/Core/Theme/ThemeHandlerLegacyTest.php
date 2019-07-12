<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated methods of ThemeHandler.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ThemeHandler
 * @group legacy
 */
class ThemeHandlerLegacyTest extends KernelTestBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->themeHandler = \Drupal::service('theme_handler');
  }

  /**
   * @covers ::install
   * @covers ::uninstall
   * @expectedDeprecation \Drupal\Core\Extension\ThemeHandlerInterface::install() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Extension\ThemeInstallerInterface::install() instead. See https://www.drupal.org/node/3017233
   * @expectedDeprecation \Drupal\Core\Extension\ThemeHandlerInterface::uninstall() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Extension\ThemeInstallerInterface::uninstall() instead. See https://www.drupal.org/node/3017233
   */
  public function testInstallUninstall() {
    $theme = 'seven';

    $this->assertFalse($this->themeHandler->themeExists($theme));
    $this->assertEquals(TRUE, $this->themeHandler->install([$theme]));
    $this->assertTrue($this->themeHandler->themeExists($theme));
    $this->themeHandler->uninstall([$theme]);
    $this->assertFalse($this->themeHandler->themeExists($theme));
  }

}
