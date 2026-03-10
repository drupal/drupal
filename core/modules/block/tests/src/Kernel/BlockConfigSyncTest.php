<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests that blocks are not created during config sync.
 */
#[Group('block')]
#[RunTestsInSeparateProcesses]
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
      ->install(['stark']);

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
   */
  #[TestWith([TRUE, NULL])]
  #[TestWith([FALSE, "test_theme_test_block"])]
  public function testNoBlocksCreatedDuringConfigSync(bool $syncing, ?string $expected_block_id): void {
    \Drupal::service(ConfigInstallerInterface::class)
      ->setSyncing($syncing);

    // Install a theme that does not provide blocks to ensure that the syncing
    // flag specifically is verified and blocks are created when syncing is off.
    \Drupal::service(ThemeInstallerInterface::class)
      ->install(['test_theme']);

    // This should hold true if the "current" install profile triggers an
    // invocation of hook_modules_installed().
    \Drupal::moduleHandler()->invoke('block', 'modules_installed', [['testing'], $syncing]);

    $this->assertSame($expected_block_id, Block::load('test_theme_test_block')?->id());
  }

}
