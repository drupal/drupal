<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Test SimplePageVariant main content rendering fallback page display variant.
 *
 * @group system
 */
class MainContentFallbackTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $adminUser;
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer modules',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a web user.
    $this->webUser = $this->drupalCreateUser(['access user profiles']);
  }

  /**
   * Tests availability of main content: Drupal falls back to SimplePageVariant.
   */
  public function testMainContentFallback() {
    $edit = [];
    // Uninstall the block module.
    $edit['uninstall[block]'] = 'block';
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('block'), 'Block module uninstall.');

    // When Block module is not installed and BlockPageVariant is not available,
    // Drupal should fall back to SimplePageVariant. Both for the admin and the
    // front-end theme.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertSession()->fieldExists('site_name');
    $this->drupalGet('system-test/main-content-fallback');
    $this->assertSession()->pageTextContains('Content to test main content fallback');
    // Request a user* page and see if it is displayed.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->webUser->id() . '/edit');
    $this->assertSession()->fieldExists('mail');

    // Enable the block module again.
    $this->drupalLogin($this->adminUser);
    $edit = [];
    $edit['modules[block][enable]'] = 'block';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('Module Block has been enabled.');
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('block'), 'Block module re-enabled.');
  }

}
