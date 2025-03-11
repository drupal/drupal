<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * @covers \Drupal\block\Plugin\ConfigAction\PlaceBlock
 * @covers \Drupal\block\Plugin\ConfigAction\PlaceBlockDeriver
 * @group block
 */
class ConfigActionsTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'system', 'user'];

  /**
   * The configuration action manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get(ThemeInstallerInterface::class)->install([
      'olivero',
      'claro',
      'umami',
    ]);
    $this->config('system.theme')
      ->set('default', 'olivero')
      ->set('admin', 'claro')
      ->save();
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   * Tests the application of entity method actions on a block.
   */
  public function testEntityMethodActions(): void {
    $block = $this->placeBlock('system_messages_block', ['theme' => 'olivero']);
    $this->assertSame('content', $block->getRegion());
    $this->assertSame(0, $block->getWeight());

    $this->configActionManager->applyAction(
      'entity_method:block.block:setRegion',
      $block->getConfigDependencyName(),
      'highlighted',
    );
    $this->configActionManager->applyAction(
      'entity_method:block.block:setWeight',
      $block->getConfigDependencyName(),
      -10,
    );

    $block = Block::load($block->id());
    $this->assertSame('highlighted', $block->getRegion());
    $this->assertSame(-10, $block->getWeight());
  }

  /**
   * @testWith ["placeBlockInDefaultTheme"]
   *           ["placeBlockInAdminTheme"]
   */
  public function testPlaceBlockActionOnlyWorksOnBlocks(string $action): void {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("The \"user_role\" entity does not support the \"$action\" config action.");
    $this->configActionManager->applyAction($action, 'user.role.anonymous', []);
  }

  /**
   * Verifies placeBlockInDefaultTheme action doesn't alter an existing block.
   */
  public function testPlaceBlockActionDoesNotChangeExistingBlock(): void {
    $extant_region = Block::load('olivero_powered')->getRegion();
    $this->assertNotSame('content', $extant_region);

    $this->configActionManager->applyAction('placeBlockInDefaultTheme', 'block.block.olivero_powered', [
      'plugin' => 'system_powered_by_block',
      'region' => [
        'olivero' => 'content',
      ],
    ]);
    // The extant block should be unchanged.
    $this->assertSame($extant_region, Block::load('olivero_powered')->getRegion());
  }

  /**
   * @testWith ["placeBlockInDefaultTheme", "olivero", "header"]
   *           ["placeBlockInAdminTheme", "claro", "page_bottom"]
   */
  public function testPlaceBlockInDynamicRegion(string $action, string $expected_theme, string $expected_region): void {
    $this->configActionManager->applyAction($action, 'block.block.test_block', [
      'plugin' => 'system_powered_by_block',
      'region' => [
        'olivero' => 'header',
        'claro' => 'page_bottom',
      ],
      'default_region' => 'content',
    ]);

    $block = Block::load('test_block');
    $this->assertInstanceOf(Block::class, $block);
    $this->assertSame('system_powered_by_block', $block->getPluginId());
    $this->assertSame($expected_theme, $block->getTheme());
    $this->assertSame($expected_region, $block->getRegion());

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('Cannot determine which region to place this block into, because no default region was provided.');
    $this->configActionManager->applyAction($action, 'block.block.no_region', [
      'plugin' => 'system_powered_by_block',
      'region' => [],
    ]);
  }

  /**
   * @testWith ["placeBlockInDefaultTheme", "olivero"]
   *           ["placeBlockInAdminTheme", "claro"]
   */
  public function testPlaceBlockInStaticRegion(string $action, string $expected_theme): void {
    $this->configActionManager->applyAction($action, 'block.block.test_block', [
      'plugin' => 'system_powered_by_block',
      'region' => 'content',
    ]);

    $block = Block::load('test_block');
    $this->assertInstanceOf(Block::class, $block);
    $this->assertSame('system_powered_by_block', $block->getPluginId());
    $this->assertSame($expected_theme, $block->getTheme());
    $this->assertSame('content', $block->getRegion());
  }

  /**
   * Tests placing a block in the default theme's region.
   */
  public function testPlaceBlockInDefaultRegion(): void {
    $this->config('system.theme')->set('default', 'umami')->save();
    $this->testPlaceBlockInDynamicRegion('placeBlockInDefaultTheme', 'umami', 'content');
  }

  /**
   * Tests placing a block at the first and last position in a region.
   */
  public function testPlaceBlockAtPosition(): void {
    // Ensure there's at least one block already in the region.
    $block = Block::create([
      'id' => 'block_1',
      'theme' => 'olivero',
      'region' => 'content_above',
      'weight' => 0,
      'plugin' => 'system_powered_by_block',
    ]);
    $block->save();

    $this->configActionManager->applyAction('placeBlockInDefaultTheme', 'block.block.first', [
      'plugin' => $block->getPluginId(),
      'region' => [
        $block->getTheme() => $block->getRegion(),
      ],
      'position' => 'first',
    ]);
    $this->configActionManager->applyAction('placeBlockInDefaultTheme', 'block.block.last', [
      'plugin' => $block->getPluginId(),
      'region' => [
        $block->getTheme() => $block->getRegion(),
      ],
      'position' => 'last',
    ]);

    // Query for blocks in the region, ordered by weight.
    $blocks = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('block')
      ->getQuery()
      ->condition('theme', $block->getTheme())
      ->condition('region', $block->getRegion())
      ->sort('weight', 'ASC')
      ->execute();
    $this->assertGreaterThanOrEqual(3, $blocks);
    $this->assertSame('first', key($blocks));
    $this->assertSame('last', end($blocks));
  }

  /**
   * Tests using the PlaceBlock action in an empty region.
   */
  public function testPlaceBlockInEmptyRegion(): void {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('block')
      ->getQuery()
      ->count()
      ->condition('theme', 'olivero')
      ->condition('region', 'footer_top');
    $this->assertSame(0, $query->execute());

    // Place a block in that region.
    $this->configActionManager->applyAction('placeBlockInDefaultTheme', 'block.block.test', [
      'plugin' => 'system_powered_by_block',
      'region' => [
        'olivero' => 'footer_top',
      ],
      'position' => 'first',
    ]);
    $this->assertSame(1, $query->execute());
  }

}
