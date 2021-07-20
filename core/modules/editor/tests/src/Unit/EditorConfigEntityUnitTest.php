<?php

namespace Drupal\Tests\editor\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\editor\Entity\Editor
 * @group editor
 */
class EditorConfigEntityUnitTest extends UnitTestCase {

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
   * The editor plugin manager used for testing.
   *
   * @var \Drupal\editor\Plugin\EditorManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $editorPluginManager;

  /**
   * Editor plugin ID.
   *
   * @var string
   */
  protected $editorId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->editorId = $this->randomMachineName();
    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->willReturn('editor');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $this->editorPluginManager = $this->getMockBuilder('Drupal\editor\Plugin\EditorManager')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.editor', $this->editorPluginManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $format_id = 'filter.format.test';
    $values = ['editor' => $this->editorId, 'format' => $format_id];

    $plugin = $this->getMockBuilder('Drupal\editor\Plugin\EditorPluginInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $plugin->expects($this->once())
      ->method('getPluginDefinition')
      ->willReturn(['provider' => 'test_module']);
    $plugin->expects($this->once())
      ->method('getDefaultSettings')
      ->willReturn([]);

    $this->editorPluginManager->expects($this->any())
      ->method('createInstance')
      ->with($this->editorId)
      ->willReturn($plugin);

    $entity = new Editor($values, $this->entityTypeId);

    $filter_format = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $filter_format->expects($this->once())
      ->method('getConfigDependencyName')
      ->willReturn('filter.format.test');

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with($format_id)
      ->willReturn($filter_format);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('filter_format')
      ->willReturn($storage);

    $dependencies = $entity->calculateDependencies()->getDependencies();
    $this->assertContains('test_module', $dependencies['module']);
    $this->assertContains('filter.format.test', $dependencies['config']);
  }

}
