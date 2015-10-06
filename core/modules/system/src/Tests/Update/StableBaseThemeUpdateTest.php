<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\StableBaseThemeUpdateTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Tests the upgrade path for introducing the Stable base theme.
 *
 * @see https://www.drupal.org/node/2575421
 *
 * @group system
 */
class StableBaseThemeUpdateTest extends UpdatePathTestBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.stable-base-theme-2575421.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->themeHandler = $this->container->get('theme_handler');
    $this->themeHandler->refreshInfo();
  }

  /**
   * Tests that the Stable base theme is installed if necessary.
   */
  public function testUpdateHookN() {
    $this->assertTrue($this->themeHandler->themeExists('test_stable'));
    $this->assertFalse($this->themeHandler->themeExists('stable'));

    $this->runUpdates();

    // Refresh the theme handler now that Stable has been installed.
    $this->themeHandler->refreshInfo();
    $this->assertTrue($this->themeHandler->themeExists('stable'));
  }

}
