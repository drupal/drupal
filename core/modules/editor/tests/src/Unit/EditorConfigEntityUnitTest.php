<?php

namespace Drupal\Tests\editor\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityManager;
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
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * The editor plugin manager used for testing.
   *
   * @var \Drupal\editor\Plugin\EditorManager|\PHPUnit_Framework_MockObject_MockObject
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
  protected function setUp() {
    $this->editorId = $this->randomMachineName();
    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('editor'));

    $this->entityTypeManager = $this->getMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->editorPluginManager = $this->getMockBuilder('Drupal\editor\Plugin\EditorManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_manager = new EntityManager();

    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.editor', $this->editorPluginManager);
    // Inject the container into entity.manager so it can defer to
    // entity_type.manager.
    $entity_manager->setContainer($container);
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
      ->will($this->returnValue(['provider' => 'test_module']));
    $plugin->expects($this->once())
      ->method('getDefaultSettings')
      ->will($this->returnValue([]));

    $this->editorPluginManager->expects($this->any())
      ->method('createInstance')
      ->with($this->editorId)
      ->will($this->returnValue($plugin));

    $entity = new Editor($values, $this->entityTypeId);

    $filter_format = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $filter_format->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('filter.format.test'));

    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with($format_id)
      ->will($this->returnValue($filter_format));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('filter_format')
      ->will($this->returnValue($storage));

    $dependencies = $entity->calculateDependencies()->getDependencies();
    $this->assertContains('test_module', $dependencies['module']);
    $this->assertContains('filter.format.test', $dependencies['config']);
  }

}
