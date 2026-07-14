<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\editor\Entity\Editor.
 */
#[CoversClass(Editor::class)]
#[Group('editor')]
class EditorConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $uuid;

  /**
   * The editor plugin manager used for testing.
   */
  protected EditorManager&MockObject $editorPluginManager;

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
    parent::setUp();

    $this->editorId = $this->randomMachineName();
    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->createStub(EntityTypeInterface::class);
    $this->entityType
      ->method('getProvider')
      ->willReturn('editor');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->uuid = $this->createStub(UuidInterface::class);

    $this->editorPluginManager = $this->createMock(EditorManager::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.editor', $this->editorPluginManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests calculate dependencies.
   */
  public function testCalculateDependencies(): void {
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

    $this->editorPluginManager->expects($this->atLeastOnce())
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
