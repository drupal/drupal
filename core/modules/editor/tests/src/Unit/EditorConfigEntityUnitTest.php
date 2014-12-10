<?php

/**
 * @file
 * Contains \Drupal\Tests\editor\Unit\EditorConfigEntityUnitTest.
 */

namespace Drupal\Tests\editor\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

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

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->editorPluginManager = $this->getMockBuilder('Drupal\editor\Plugin\EditorManager')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.editor', $this->editorPluginManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $format_id = 'filter.format.test';
    $values = array('editor' => $this->editorId, 'format' => $format_id);

    $plugin = $this->getMockBuilder('Drupal\editor\Plugin\EditorPluginInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $plugin->expects($this->once())
      ->method('getPluginDefinition')
      ->will($this->returnValue(array('provider' => 'test_module')));
    $plugin->expects($this->once())
      ->method('getDefaultSettings')
      ->will($this->returnValue(array()));

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

    $this->entityManager->expects($this->once())
                        ->method('getStorage')
                        ->with('filter_format')
                        ->will($this->returnValue($storage));

    $dependencies = $entity->calculateDependencies();
    $this->assertContains('test_module', $dependencies['module']);
    $this->assertContains('filter.format.test', $dependencies['config']);
  }

}
