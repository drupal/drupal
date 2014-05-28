<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockHiddenRegionTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that hidden regions do not inherit blocks when a theme is enabled.
 */
class BlockHiddenRegionTest extends WebTestBase {

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test', 'search');

  public static function getInfo() {
    return array(
      'name' => 'Blocks not in hidden region',
      'description' => 'Checks that a newly enabled theme does not inherit blocks to its hidden regions.',
      'group' => 'Block',
    );
  }

  function setUp() {
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
   * Tests that hidden regions do not inherit blocks when a theme is enabled.
   */
  public function testBlockNotInHiddenRegion() {

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertText('Search', 'Block was displayed on the front page.');

    // Enable "block_test_theme" and set it as the default theme.
    $theme = 'block_test_theme';
    theme_enable(array($theme));
    \Drupal::config('system.theme')
      ->set('default', $theme)
      ->save();
    // Enabling a theme will cause the kernel terminate event to rebuild the
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
