<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests that blocks are not created during config sync.
 *
 * @group block
 */
class BlockConfigSyncTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service(ThemeInstallerInterface::class)
      ->install(['stark', 'claro']);

    // Delete all existing blocks.
    foreach (Block::loadMultiple() as $block) {
      $block->delete();
    }

    // Set the default theme.
    $this->config('system.theme')
      ->set('default', 'stark')
      ->save();

    // Create a block for the default theme to be copied later.
    Block::create([
      'id' => 'test_block',
      'plugin' => 'system_powered_by_block',
      'region' => 'content',
      'theme' => 'stark',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->setParameter('install_profile', 'testing');
  }

  /**
   * Tests blocks are not created during config sync.
   *
   * @param bool $syncing
   *   Whether or not config is syncing when the hook is invoked.
   * @param string|null $expected_block_id
   *   The expected ID of the block that should be created, or NULL if no block
   *   should be created.
   *
   * @testWith [true, null]
   *   [false, "claro_test_block"]
   */
  public function testNoBlocksCreatedDuringConfigSync(bool $syncing, ?string $expected_block_id): void {
    \Drupal::service(ConfigInstallerInterface::class)
      ->setSyncing($syncing);

    // Invoke the hook that should skip block creation due to config sync.
    \Drupal::moduleHandler()->invoke('block', 'themes_installed', [['claro']]);
    // This should hold true if the "current" install profile triggers an
    // invocation of hook_modules_installed().
    \Drupal::moduleHandler()->invoke('block', 'modules_installed', [['testing'], $syncing]);

    $this->assertSame($expected_block_id, Block::load('claro_test_block')?->id());
  }

}
