<?php

/**
 * @file
 * Definition of Drupal\block\Tests\NewDefaultThemeBlocksTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test blocks correctly initialized when picking a new default theme.
 */
class NewDefaultThemeBlocksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'New default theme blocks',
      'description' => 'Checks that the new default theme gets blocks.',
      'group' => 'Block',
    );
  }

  /**
   * Check the enabled Bartik blocks are correctly copied over.
   */
  function testNewDefaultThemeBlocks() {
    $default_theme = config('system.theme')->get('default');

    // Add several block instances.
    $this->adminUser = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($this->adminUser);

    // Add two instances of the user login block.
    $this->drupalPlaceBlock('user_login_block');
    $this->drupalPlaceBlock('user_login_block');

    // Add an instance of a different block.
    $this->drupalPlaceBlock('system_powered_by_block');
    $this->drupalLogout($this->adminUser);

    // Enable a different theme.
    $new_theme = 'bartik';
    $this->assertFalse($new_theme == $default_theme, 'The new theme is different from the previous default theme.');
    theme_enable(array($new_theme));
    config('system.theme')
      ->set('default', $new_theme)
      ->save();

    // Ensure that the new theme has all the blocks as the previous default.
    // @todo Replace the string manipulation below once the configuration
    //   system provides a method for extracting an ID in a given namespace.
    $default_prefix = "block.block.$default_theme";
    $new_prefix = "block.block.$new_theme";
    $default_block_names = config_get_storage_names_with_prefix($default_prefix);
    $new_blocks = array_flip(config_get_storage_names_with_prefix($new_prefix));
    $this->assertTrue(count($default_block_names) == count($new_blocks), 'The new default theme has the same number of blocks as the previous theme.');
    foreach ($default_block_names as $default_block_name) {
      // Make sure the configuration object name is in the expected format.
      if (strpos($default_block_name, $default_prefix) === 0) {
        // Remove the matching block from the list of blocks in the new theme.
        // E.g., if the old theme has block.block.stark.admin,
        // unset block.block.bartik.admin.
        $id = substr($default_block_name, (strlen($default_prefix) + 1));
        unset($new_blocks[$new_prefix . '.' . $id]);
      }
      else {
        $this->fail(format_string(
          '%block is not an expected block instance name.',
          array(
            '%block' => $default_block_name,
          )
        ));
      }
    }
    $this->assertTrue(empty($new_blocks), 'The new theme has exactly the same blocks as the previous default theme.');
  }

}
