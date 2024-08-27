<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that blocks are not added to hidden regions on theme installation.
 *
 * @group block
 */
class BlockHiddenRegionTest extends BrowserTestBase {

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_test', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
      'search content',
    ]);

    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('search_form_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests that hidden regions do not inherit blocks when a theme is installed.
   */
  public function testBlockNotInHiddenRegion(): void {

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Search');

    // Install "block_test_theme" and set it as the default theme.
    $theme = 'block_test_theme';
    // We need to install a non-hidden theme so that there is more than one
    // local task.
    \Drupal::service('theme_installer')->install([$theme, 'stark']);
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    // Installing a theme will cause the kernel terminate event to rebuild the
    // router. Simulate that here.
    \Drupal::service('router.builder')->rebuildIfNeeded();

    // Ensure that "block_test_theme" is set as the default theme.
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Block test theme');

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Search');
  }

}
