<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the block demo page with admin themes.
 *
 * @group block
 */
class BlockDemoTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Check for the accessibility of the admin block demo page.
   */
  public function testBlockDemo() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(['administer blocks', 'administer themes']);
    $this->drupalLogin($admin_user);

    // Confirm we have access to the block demo page for the default theme.
    $config = $this->container->get('config.factory')->get('system.theme');
    $default_theme = $config->get('default');
    $this->drupalGet('admin/structure/block/demo/' . $default_theme);
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/structure/block');
    $this->assertNoLinkByHref('admin/structure/block/list/' . $default_theme);

    // All available themes in core.
    $available_themes = [
      'bartik',
      'classy',
      'seven',
      'stark',
    ];

    // All available themes minute minus the default theme.
    $themes = array_diff($available_themes, [$default_theme]);

    foreach ($themes as $theme) {
      // Install theme.
      $this->container->get('theme_installer')->install([$theme]);
      // Confirm access to the block demo page for the theme.
      $this->drupalGet('admin/structure/block/demo/' . $theme);
      $this->assertResponse(200);
      // Confirm existence of link for "Exit block region demonstration".
      $this->assertLinkByHref('admin/structure/block/list/' . $theme);
    }

    // Confirm access to the block demo page is denied for an invalid theme.
    $this->drupalGet('admin/structure/block/demo/invalid_theme');
    $this->assertResponse(403);
  }

}
