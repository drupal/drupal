<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the new default theme gets blocks.
 *
 * @group block
 */
class NewDefaultThemeBlocksTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * Check the enabled Bartik blocks are correctly copied over.
   */
  public function testNewDefaultThemeBlocks() {
    $default_theme = $this->config('system.theme')->get('default');

    // Add two instances of the user login block.
    $this->drupalPlaceBlock('user_login_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);
    $this->drupalPlaceBlock('user_login_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);

    // Add an instance of a different block.
    $this->drupalPlaceBlock('system_powered_by_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);

    // Install a different theme.
    $new_theme = 'bartik';
    $this->assertFalse($new_theme == $default_theme, 'The new theme is different from the previous default theme.');
    \Drupal::service('theme_handler')->install([$new_theme]);
    $this->config('system.theme')
      ->set('default', $new_theme)
      ->save();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $block_storage */
    $block_storage = $this->container->get('entity_type.manager')->getStorage('block');

    // Ensure that the new theme has all the blocks as the previous default.
    $default_block_names = $block_storage->getQuery()
      ->condition('theme', $default_theme)
      ->execute();
    $new_blocks = $block_storage->getQuery()
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

    // Install a hidden base theme and ensure blocks are not copied.
    $base_theme = 'test_basetheme';
    \Drupal::service('theme_handler')->install([$base_theme]);
    $new_blocks = $block_storage->getQuery()
      ->condition('theme', $base_theme)
      ->execute();
    $this->assertTrue(empty($new_blocks), 'Installing a hidden base theme does not copy blocks from the default theme.');
  }

}
