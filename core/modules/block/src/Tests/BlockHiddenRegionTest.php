<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockHiddenRegionTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that a newly installed theme does not inherit blocks to its hidden
 * regions.
 *
 * @group block
 */
class BlockHiddenRegionTest extends WebTestBase {

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test', 'search');

  protected function setUp() {
    parent::setUp();

    // Create administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'administer themes',
      'search content',
      )
    );

    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('search_form_block');
  }

  /**
   * Tests that hidden regions do not inherit blocks when a theme is installed.
   */
  public function testBlockNotInHiddenRegion() {

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertText('Search', 'Block was displayed on the front page.');

    // Install "block_test_theme" and set it as the default theme.
    $theme = 'block_test_theme';
    \Drupal::service('theme_handler')->install(array($theme));
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    // Installing a theme will cause the kernel terminate event to rebuild the
    // router. Simulate that here.
    \Drupal::service('router.builder')->rebuildIfNeeded();

    // Ensure that "block_test_theme" is set as the default theme.
    $this->drupalGet('admin/structure/block');
    $this->assertText('Block test theme(' . t('active tab') . ')', 'Default local task on blocks admin page is the block test theme.');

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertText('Search', 'Block was displayed on the front page.');
  }

}
