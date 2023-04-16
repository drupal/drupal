<?php

namespace Drupal\Tests\block\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Tests\Core\Plugin\Fixtures\TestConfigurablePlugin;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\Entity\Block
 * @group block
 */
class BlockConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuid;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->willReturn('block');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->themeHandler = $this->prophesize(ThemeHandlerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('module_handler', $this->moduleHandler->reveal());
    $container->set('theme_handler', $this->themeHandler->reveal());
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->themeHandler->themeExists('stark')->willReturn(TRUE);
    $values = ['theme' => 'stark'];
    // Mock the entity under test so that we can mock getPluginCollections().
    $entity = $this->getMockBuilder('\Drupal\block\Entity\Block')
      ->setConstructorArgs([$values, $this->entityTypeId])
      ->onlyMethods(['getPluginCollections'])
      ->getMock();
    // Create a configurable plugin that would add a dependency.
    $instance_id = $this->randomMachineName();
    $this->moduleHandler->moduleExists('test')->willReturn(TRUE);
    $instance = new TestConfigurablePlugin([], $instance_id, ['provider' => 'test']);

    // Create a plugin collection to contain the instance.
    $plugin_collection = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultLazyPluginCollection')
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $plugin_collection->expects($this->atLeastOnce())
      ->method('get')
      ->with($instance_id)
      ->willReturn($instance);
    $plugin_collection->addInstanceId($instance_id);

    // Return the mocked plugin collection.
    $entity->expects($this->once())
      ->method('getPluginCollections')
      ->willReturn([$plugin_collection]);

    $dependencies = $entity->calculateDependencies()->getDependencies();
    $this->assertContains('test', $dependencies['module']);
    $this->assertContains('stark', $dependencies['theme']);
  }

}
