<?php

namespace Drupal\Tests\block\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests that the new default theme gets blocks.
 *
 * @group block
 */
class NewDefaultThemeBlocksTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'system',
  ];

  /**
   * Check the enabled Bartik blocks are correctly copied over.
   */
  public function testNewDefaultThemeBlocks() {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $default_theme = $this->config('system.theme')->get('default');

    // Add two instances of the user login block.
    $this->placeBlock('user_login_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);
    $this->placeBlock('user_login_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);

    // Add an instance of a different block.
    $this->placeBlock('system_powered_by_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);

    // Install a different theme.
    $new_theme = 'bartik';
    // The new theme is different from the previous default theme.
    $this->assertNotEquals($new_theme, $default_theme);

    $theme_installer->install([$new_theme]);
    $this->config('system.theme')
      ->set('default', $new_theme)
      ->save();

    $block_storage = $this->container->get('entity_type.manager')->getStorage('block');

    // Ensure that the new default theme has the same blocks as the previous
    // default theme.
    $default_block_names = $block_storage->getQuery()
      ->condition('theme', $default_theme)
      ->execute();
    $new_blocks = $block_storage->getQuery()
      ->condition('theme', $new_theme)
      ->execute();
    $this->assertSameSize($default_block_names, $new_blocks);

    foreach ($default_block_names as $default_block_name) {
      // Remove the matching block from the list of blocks in the new theme.
      // E.g., if the old theme has block.block.stark_admin,
      // unset block.block.bartik_admin.
      unset($new_blocks[str_replace($default_theme . '_', $new_theme . '_', $default_block_name)]);
    }
    $this->assertEmpty($new_blocks);

    // Install a hidden base theme and ensure blocks are not copied.
    $base_theme = 'test_basetheme';
    $theme_installer->install([$base_theme]);
    $new_blocks = $block_storage->getQuery()
      ->condition('theme', $base_theme)
      ->execute();
    // Installing a hidden base theme does not copy the blocks from the default
    // theme.
    $this->assertEmpty($new_blocks);
  }

}
