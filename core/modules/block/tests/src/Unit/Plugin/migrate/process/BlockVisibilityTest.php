<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\Plugin\migrate\process\BlockVisibilityTest.
 */

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockVisibility;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the block_visibility process plugin.
 *
 * @coversDefaultClass \Drupal\block\Plugin\migrate\process\BlockVisibility
 * @group block
 */
class BlockVisibilityTest extends MigrateProcessTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $this->plugin = new BlockVisibility([], 'block_visibility_pages', [], $this->moduleHandler->reveal(), $migration_plugin->reveal());
  }

  /**
   * @covers ::transform
   */
  public function testTransformNoData() {
    $transformed_value = $this->plugin->transform([0, '', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEmpty($transformed_value);
  }

  /**
   * @covers ::transform
   */
  public function testTransformSinglePageWithFront() {
    $visibility = $this->plugin->transform([0, '<front>', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('request_path', $visibility['request_path']['id']);
    $this->assertTrue($visibility['request_path']['negate']);
    $this->assertSame('<front>', $visibility['request_path']['pages']);
  }

  /**
   * @covers ::transform
   */
  public function testTransformMultiplePagesWithFront() {
    $visibility = $this->plugin->transform([1, "foo\n/bar\rbaz\r\n<front>", []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('request_path', $visibility['request_path']['id']);
    $this->assertFalse($visibility['request_path']['negate']);
    $this->assertSame("/foo\n/bar\n/baz\n<front>", $visibility['request_path']['pages']);
  }

  /**
   * @covers ::transform
   */
  public function testTransformPhpEnabled() {
    $this->moduleHandler->moduleExists('php')->willReturn(TRUE);
    $visibility = $this->plugin->transform([2, '<?php', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('php', $visibility['php']['id']);
    $this->assertFalse($visibility['php']['negate']);
    $this->assertSame('<?php', $visibility['php']['php']);
  }

  /**
   * @covers ::transform
   */
  public function testTransformPhpDisabled() {
    $this->moduleHandler->moduleExists('php')->willReturn(FALSE);
    $transformed_value = $this->plugin->transform([2, '<?php', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEmpty($transformed_value);
  }

}
