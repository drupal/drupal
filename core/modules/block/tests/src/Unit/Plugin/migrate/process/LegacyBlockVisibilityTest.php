<?php

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockVisibility;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the block_visibility process plugin.
 *
 * @coversDefaultClass \Drupal\block\Plugin\migrate\process\BlockVisibility
 *
 * @group block
 * @group legacy
 */
class LegacyBlockVisibilityTest extends MigrateProcessTestCase {

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
    $migrate_lookup = $this->prophesize(MigrateLookupInterface::class);
    $container = new ContainerBuilder();
    $container->set('migrate.lookup', $migrate_lookup->reveal());
    \Drupal::setContainer($container);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $this->plugin = new BlockVisibility([], 'block_visibility_pages', [], $this->moduleHandler->reveal(), $migration_plugin->reveal());
  }

  /**
   * Tests Transform.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockVisibility::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testTransformNoData() {
    $transformed_value = $this->plugin->transform([0, '', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEmpty($transformed_value);
  }

  /**
   * Tests Transform.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockVisibility::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testTransformSinglePageWithFront() {
    $visibility = $this->plugin->transform([0, '<front>', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('request_path', $visibility['request_path']['id']);
    $this->assertTrue($visibility['request_path']['negate']);
    $this->assertSame('<front>', $visibility['request_path']['pages']);
  }

  /**
   * Tests Transform.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockVisibility::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testTransformMultiplePagesWithFront() {
    $visibility = $this->plugin->transform([1, "foo\n/bar\rbaz\r\n<front>", []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('request_path', $visibility['request_path']['id']);
    $this->assertFalse($visibility['request_path']['negate']);
    $this->assertSame("/foo\n/bar\n/baz\n<front>", $visibility['request_path']['pages']);
  }

  /**
   * Tests Transform.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockVisibility::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testTransformPhpEnabled() {
    $this->moduleHandler->moduleExists('php')->willReturn(TRUE);
    $visibility = $this->plugin->transform([2, '<?php', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('php', $visibility['php']['id']);
    $this->assertFalse($visibility['php']['negate']);
    $this->assertSame('<?php', $visibility['php']['php']);
  }

  /**
   * Tests Transform.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockVisibility::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testTransformPhpDisabled() {
    $this->moduleHandler->moduleExists('php')->willReturn(FALSE);
    $transformed_value = $this->plugin->transform([2, '<?php', []], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEmpty($transformed_value);
  }

  /**
   * Tests Transform.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Passing a migration process plugin as the fifth argument to Drupal\block\Plugin\migrate\process\BlockVisibility::__construct is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268
   */
  public function testTransformException() {
    $this->moduleHandler->moduleExists('php')->willReturn(FALSE);
    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $this->row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->setMethods(['getSourceProperty'])
      ->getMock();
    $this->row->expects($this->exactly(2))
      ->method('getSourceProperty')
      ->willReturnMap([['bid', 99], ['module', 'foobar']]);
    $this->plugin = new BlockVisibility(['skip_php' => TRUE], 'block_visibility_pages', [], $this->moduleHandler->reveal(), $migration_plugin->reveal());
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("The block with bid '99' from module 'foobar' will have no PHP or request_path visibility configuration.");
    $this->plugin->transform([2, '<?php', []], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
