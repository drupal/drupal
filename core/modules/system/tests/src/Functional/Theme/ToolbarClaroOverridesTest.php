<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of Claro assets on a non-Claro default theme.
 *
 * @group Theme
 */
class ToolbarClaroOverridesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'test_page_test', 'shortcut', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The theme installer used in this test for enabling themes.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeInstaller = $this->container->get('theme_installer');
    $this->themeManager = $this->container->get('theme.manager');
    $this->themeInstaller->install(['claro']);

    // Create user with sufficient permissions to have the shortcut toolbar menu
    // be available.
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'access shortcuts',
      'administer shortcuts',
      'access content overview',
    ]));
  }

  /**
   * Confirm Claro assets load on a non-Claro default theme.
   */
  public function testClaroAssets() {
    $default_stylesheets = [
      'core/modules/toolbar/css/toolbar.module.css',
      'core/modules/toolbar/css/toolbar.menu.css',
      'core/modules/toolbar/css/toolbar.theme.css',
      'core/modules/toolbar/css/toolbar.icons.theme.css',
    ];

    $claro_stylesheets = [
      'core/themes/claro/css/components/toolbar.module.css',
      'core/themes/claro/css/state/toolbar.menu.css',
      'core/themes/claro/css/theme/toolbar.theme.css',
      'core/themes/claro/css/theme/toolbar.icons.theme.css',
    ];

    $this->config('system.theme')->set('admin', 'stark')->save();

    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    $admin_theme = \Drupal::configFactory()->get('system.theme')->get('admin');
    $default_theme = \Drupal::configFactory()->get('system.theme')->get('default');
    $this->assertEquals('stark', $admin_theme);
    $this->assertEquals('stark', $default_theme);

    $head = $this->getSession()->getPage()->find('css', 'head')->getHtml();

    // Confirm that Claro stylesheets are not loading, and the ones they would
    // override were Claro enabled are still loading.
    $stylesheet_positions = [];
    foreach ($default_stylesheets as $stylesheet) {
      $this->assertStringContainsString($stylesheet, $head);
      $stylesheet_positions[] = strpos($head, $stylesheet);
    }
    $sorted_stylesheet_positions = $stylesheet_positions;
    sort($sorted_stylesheet_positions);
    $this->assertEquals($sorted_stylesheet_positions, $stylesheet_positions);

    foreach ($claro_stylesheets as $stylesheet) {
      $this->assertStringNotContainsString($stylesheet, $head);
    }

    // Confirm toolbar is not processed by claro_preprocess_toolbar().
    $this->assertFalse($this->getSession()->getPage()->find('css', '#toolbar-administration')->hasAttribute('data-drupal-claro-processed-toolbar'));

    // Confirm menu--toolbar.html.twig is not loaded from Claro.
    $this->assertFalse($this->getSession()->getPage()->find('css', '.toolbar-menu')->hasClass('claro-toolbar-menu'));
    $this->assertFalse($this->getSession()->getPage()->find('css', '.toolbar')->hasClass('claro-toolbar'));

    $this->config('system.theme')->set('admin', 'claro')->save();
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    $admin_theme = \Drupal::configFactory()->get('system.theme')->get('admin');
    $default_theme = \Drupal::configFactory()->get('system.theme')->get('default');
    $this->assertEquals('claro', $admin_theme);
    $this->assertEquals('stark', $default_theme);

    $head = $this->getSession()->getPage()->find('css', 'head')->getHtml();

    // Confirm that Claro stylesheets are loading, and the ones they override
    // are not loading.
    $stylesheet_positions = [];
    foreach ($claro_stylesheets as $stylesheet) {
      $this->assertStringContainsString($stylesheet, $head);
      $stylesheet_positions[] = strpos($head, $stylesheet);
    }
    $sorted_stylesheet_positions = $stylesheet_positions;
    sort($sorted_stylesheet_positions);
    $this->assertEquals($sorted_stylesheet_positions, $stylesheet_positions);

    foreach ($default_stylesheets as $stylesheet) {
      $this->assertStringNotContainsString($stylesheet, $head);
    }

    // Confirm toolbar is processed by claro_preprocess_toolbar().
    $this->assertTrue($this->getSession()->getPage()->find('css', '#toolbar-administration')->hasAttribute('data-drupal-claro-processed-toolbar'));

    // Confirm toolbar templates are loaded from Claro.
    $this->assertTrue($this->getSession()->getPage()->find('css', '.toolbar')->hasClass('claro-toolbar'));
    $this->assertTrue($this->getSession()->getPage()->find('css', '.toolbar-menu')->hasClass('claro-toolbar-menu'));
  }

}
