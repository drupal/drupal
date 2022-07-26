<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the block system with admin themes.
 *
 * @group block
 */
class BlockAdminThemeTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Check for the accessibility of the admin theme on the block admin page.
   */
  public function testAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
    ]);
    $this->drupalLogin($admin_user);

    // Ensure that access to block admin page is denied when theme is not
    // installed.
    $this->drupalGet('admin/structure/block/list/olivero');
    $this->assertSession()->statusCodeEquals(403);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_installer')->install(['olivero']);
    $edit['admin_theme'] = 'olivero';
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('admin/structure/block/list/olivero');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Ensure contextual links are disabled in Claro theme.
   */
  public function testClaroAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'access contextual links',
      'view the administration theme',
    ]);
    $this->drupalLogin($admin_user);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_installer')->install(['claro']);
    $edit['admin_theme'] = 'claro';
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Define our block settings.
    $settings = [
      'theme' => 'claro',
      'region' => 'header',
    ];

    // Place a block.
    $block = $this->drupalPlaceBlock('local_tasks_block', $settings);

    // Open admin page.
    $this->drupalGet('admin');

    // Check if contextual link classes are unavailable.
    $this->assertSession()->responseNotContains('<div data-contextual-id="block:block=' . $block->id() . ':langcode=en"></div>');
    $this->assertSession()->responseNotContains('contextual-region');
  }

}
