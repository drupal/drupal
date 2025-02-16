<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests that a new default theme gets blocks.
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
    'user',
  ];

  /**
   * The theme installer service.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    $this->themeInstaller = $this->container->get('theme_installer');
    $this->defaultTheme = $this->config('system.theme')->get('default');
  }

  /**
   * Check the blocks are correctly copied by block_themes_installed().
   */
  public function testNewDefaultThemeBlocks(): void {
    $default_theme = $this->defaultTheme;
    $theme_installer = $this->themeInstaller;
    $theme_installer->install([$default_theme]);

    // Add two instances of the user login block.
    $this->placeBlock('user_login_block', [
      'id' => $default_theme . '_' . $this->randomMachineName(8),
    ]);
    $this->placeBlock('user_login_block', [
      'id' => $default_theme . '_' . $this->randomMachineName(8),
    ]);

    // Add an instance of a different block.
    $this->placeBlock('system_powered_by_block', [
      'id' => $default_theme . '_' . $this->randomMachineName(8),
    ]);

    // Install a different theme that does not have blocks.
    $new_theme = 'test_theme';
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
      ->accessCheck(FALSE)
      ->condition('theme', $default_theme)
      ->execute();
    $new_blocks = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('theme', $new_theme)
      ->execute();
    $this->assertSameSize($default_block_names, $new_blocks);

    foreach ($default_block_names as $default_block_name) {
      // Remove the matching block from the list of blocks in the new theme.
      // For example, if the old theme has block.block.stark_admin,
      // unset block.block.olivero_admin.
      unset($new_blocks[str_replace($default_theme . '_', $new_theme . '_', $default_block_name)]);
    }
    $this->assertEmpty($new_blocks);

    // Install a hidden base theme and ensure blocks are not copied.
    $base_theme = 'test_base_theme';
    $theme_installer->install([$base_theme]);
    $new_blocks = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('theme', $base_theme)
      ->execute();
    $this->assertEmpty($new_blocks);
  }

  /**
   * Checks that a theme block is still created when same ID exists.
   */
  public function testBlockCollision(): void {
    $default_theme = $this->defaultTheme;
    $theme_installer = $this->themeInstaller;
    $theme_installer->install([$default_theme]);

    // Add two instances of the user login block with machine
    // names that will collide.
    $this->placeBlock('user_login_block', [
      'id' => 'user_login_block',
    ]);
    $this->placeBlock('user_login_block', [
      'id' => $default_theme . '_user_login_block',
    ]);

    // Add an instance of a different block.
    $this->placeBlock('system_powered_by_block', [
      'id' => $default_theme . '_' . strtolower($this->randomMachineName(8)),
    ]);

    // Install a different theme that does not have blocks.
    $new_theme = 'test_theme';
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
      ->accessCheck(FALSE)
      ->condition('theme', $default_theme)
      ->execute();
    $new_blocks = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('theme', $new_theme)
      ->execute();
    $this->assertSameSize($default_block_names, $new_blocks);

    foreach ($default_block_names as $default_block_name) {
      // Remove the matching block from the list of blocks in the new theme.
      // For example, if the old theme has block.block.stark_admin,
      // unset block.block.olivero_admin.
      unset($new_blocks[str_replace($default_theme . '_', $new_theme . '_', $default_block_name)]);
    }
    // The test_theme_user_login_block machine name is already in use, so
    // therefore \Drupal\block\BlockRepository::getUniqueMachineName appends a
    // counter.
    unset($new_blocks[$new_theme . '_user_login_block_2']);
    $this->assertEmpty($new_blocks);

    // Install a hidden base theme and ensure blocks are not copied.
    $base_theme = 'test_base_theme';
    $theme_installer->install([$base_theme]);
    $new_blocks = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('theme', $base_theme)
      ->execute();
    // Installing a hidden base theme does not copy the blocks from the default
    // theme.
    $this->assertEmpty($new_blocks);
  }

}
