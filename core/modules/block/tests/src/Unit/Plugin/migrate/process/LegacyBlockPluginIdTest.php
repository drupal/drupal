<?php

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockPluginId;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests legacy usage of BlockPluginId.
 *
 * @group block
 * @group legacy
 *
 * @coversDefaultClass \Drupal\block\Plugin\migrate\process\BlockPluginId
 */
class LegacyBlockPluginIdTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migrate_lookup = $this->prophesize(MigrateLookupInterface::class);
    $container = new ContainerBuilder();
    $container->set('migrate.lookup', $migrate_lookup->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests legacy construction.
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockPluginId::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testConstruct() {
    $process_plugin = $this->prophesize(MigrateProcessInterface::class);
    $process_plugin->transform(1, $this->migrateExecutable, $this->row, 'destination_property')->willReturn(3);
    $block = $this->prophesize(BlockContent::class);
    $block->uuid()->willReturn('123456789');
    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->load(3)->willReturn($block->reveal());
    $plugin = new BlockPluginId([], '', [], $storage->reveal(), $process_plugin->reveal());
    $this->assertSame('block_content:123456789', $plugin->transform(['block', 1], $this->migrateExecutable, $this->row, 'destination_property'));
  }

}
