<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of Contextual's Claro assets on a non-Claro default theme.
 *
 * @group Theme
 */
class ContextualClaroOverridesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'contextual'];

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeInstaller = $this->container->get('theme_installer');
    $this->themeManager = $this->container->get('theme.manager');
    $this->themeInstaller->install(['claro']);

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    // Create a node.
    $this->drupalCreateNode();

    $this->drupalLogin($this->drupalCreateUser([
      'edit any page content',
      'delete any page content',
      'access contextual links',
    ]));
  }

  /**
   * Confirm Contextual's Claro assets load on a non-Claro default theme.
   */
  public function testClaroAssets() {
    $default_stylesheets = [
      'core/modules/contextual/css/contextual.theme.css',
      'core/modules/contextual/css/contextual.icons.theme.css',
    ];

    $claro_stylesheets = [
      'core/themes/claro/css/theme/contextual.theme.css',
      'core/themes/claro/css/theme/contextual.icons.theme.css',
      'core/themes/claro/css/components/icon-link.css',
    ];

    $this->config('system.theme')->set('admin', 'stark')->save();
    $admin_theme = \Drupal::configFactory()->get('system.theme')->get('admin');
    $default_theme = \Drupal::configFactory()->get('system.theme')->get('default');
    $this->assertEquals('stark', $admin_theme);
    $this->assertEquals('stark', $default_theme);

    $this->drupalGet('node/1');
    $head = $this->getSession()->getPage()->find('css', 'head')->getHtml();

    // Confirm that Claro's Contextual assets are not loading, and the ones they
    // would override if Claro was the admin theme are still loading.
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
    $this->assertSession()->elementNotExists('css', 'script[src*="core/themes/claro/js/contextual.js"]');

    $this->config('system.theme')->set('admin', 'claro')->save();
    $admin_theme = \Drupal::configFactory()->get('system.theme')->get('admin');
    $default_theme = \Drupal::configFactory()->get('system.theme')->get('default');
    $this->assertEquals('claro', $admin_theme);
    $this->assertEquals('stark', $default_theme);

    $this->drupalGet('node/1');
    $head = $this->getSession()->getPage()->find('css', 'head')->getHtml();

    // Confirm that Claro's Contextual assets are loading, and the ones they
    // override are not loading.
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

    $this->assertSession()->elementExists('css', 'script[src*="core/themes/claro/js/contextual.js"]');
  }

}
