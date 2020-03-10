<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for introducing the Stable base theme.
 *
 * @see https://www.drupal.org/node/2575421
 *
 * @group system
 * @group legacy
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
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.stable-base-theme-2575421.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Make a test theme without a base_theme. The update fixture
    // 'drupal-8.stable-base-theme-2575421.php' will enable this theme.
    // Any theme without a 'base theme' property will have its
    // 'base theme' property set to stable. Because this behavior is deprecated
    // we copy this theme in to '/themes' for only this test to avoid most tests
    // having the deprecation notice.
    // @see \Drupal\Core\Extension\ThemeExtensionList::createExtensionInfo()
    mkdir($this->siteDirectory . '/themes');
    mkdir($this->siteDirectory . '/themes/test_stable');
    copy(DRUPAL_ROOT . '/core/tests/fixtures/test_stable/test_stable.info.yml', $this->siteDirectory . '/themes/test_stable/test_stable.info.yml');
    copy(DRUPAL_ROOT . '/core/tests/fixtures/test_stable/test_stable.theme', $this->siteDirectory . '/themes/test_stable/test_stable.theme');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->themeHandler = $this->container->get('theme_handler');

  }

  /**
   * Tests that the Stable base theme is installed if necessary.
   *
   * @expectedDeprecation There is no `base theme` property specified in the test_stable.info.yml file. The optionality of the `base theme` property is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. All Drupal 8 themes must add `base theme: stable` to their *.info.yml file for them to continue to work as-is in future versions of Drupal. Drupal 9 requires the `base theme` property to be specified. See https://www.drupal.org/node/3066038
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
