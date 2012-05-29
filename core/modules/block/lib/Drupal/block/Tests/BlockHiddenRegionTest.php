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
  public static function getInfo() {
    return array(
      'name' => 'Blocks not in hidden region',
      'description' => 'Checks that a newly enabled theme does not inherit blocks to its hidden regions.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp(array('block', 'block_test', 'search'));

    // Enable Search block in default theme.
    db_merge('block')
      ->key(array(
        'module' => 'search',
        'delta' => 'form',
        'theme' => variable_get('theme_default', 'stark'),
      ))
      ->fields(array(
        'status' => 1,
        'weight' => -1,
        'region' => 'sidebar_first',
        'pages' => '',
        'cache' => -1,
      ))
      ->execute();
  }

  /**
   * Tests that hidden regions do not inherit blocks when a theme is enabled.
   */
  function testBlockNotInHiddenRegion() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer themes', 'search content'));
    $this->drupalLogin($admin_user);

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertText('Search', t('Block was displayed on the front page.'));

    // Enable "block_test_theme" and set it as the default theme.
    $theme = 'block_test_theme';
    theme_enable(array($theme));
    variable_set('theme_default', $theme);
    menu_router_rebuild();

    // Ensure that "block_test_theme" is set as the default theme.
    $this->drupalGet('admin/structure/block');
    $this->assertText('Block test theme(' . t('active tab') . ')', t('Default local task on blocks admin page is the block test theme.'));

    // Ensure that the search form block is displayed.
    $this->drupalGet('');
    $this->assertText('Search', t('Block was displayed on the front page.'));
  }
}
