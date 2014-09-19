<?php

/**
 * @file
 * Definition of Drupal\block\Tests\NewDefaultThemeBlocksTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that the new default theme gets blocks.
 *
 * @group block
 */
class NewDefaultThemeBlocksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  /**
   * Check the enabled Bartik blocks are correctly copied over.
   */
  function testNewDefaultThemeBlocks() {
    $default_theme = \Drupal::config('system.theme')->get('default');

    // Add two instances of the user login block.
    $this->drupalPlaceBlock('user_login_block', array(
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ));
    $this->drupalPlaceBlock('user_login_block', array(
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ));

    // Add an instance of a different block.
    $this->drupalPlaceBlock('system_powered_by_block', array(
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ));

    // Install a different theme.
    $new_theme = 'bartik';
    $this->assertFalse($new_theme == $default_theme, 'The new theme is different from the previous default theme.');
    \Drupal::service('theme_handler')->install(array($new_theme));
    \Drupal::config('system.theme')
      ->set('default', $new_theme)
      ->save();

    // Ensure that the new theme has all the blocks as the previous default.
    $default_block_names = $this->container->get('entity.query')->get('block')
      ->condition('theme', $default_theme)
      ->execute();
    $new_blocks = $this->container->get('entity.query')->get('block')
      ->condition('theme', $new_theme)
      ->execute();
    $this->assertTrue(count($default_block_names) == count($new_blocks), 'The new default theme has the same number of blocks as the previous theme.');
    foreach ($default_block_names as $default_block_name) {
      // Remove the matching block from the list of blocks in the new theme.
      // E.g., if the old theme has block.block.stark_admin,
      // unset block.block.bartik_admin.
      unset($new_blocks[str_replace($default_theme . '_', $new_theme . '_', $default_block_name)]);
    }
    $this->assertTrue(empty($new_blocks), 'The new theme has exactly the same blocks as the previous default theme.');
  }

}
