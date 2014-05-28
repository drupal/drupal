<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditorConfigEntityUnitTest.
 */

namespace Drupal\editor\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\editor\Entity\Editor
 *
 * @group Drupal
 * @group Config
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
   * The module handler used for testing.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * Editor plugin ID.
   *
   * @var string
   */
  protected $editorId;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\editor\Entity\Editor unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->editorId = $this->randomName();
    $this->entityTypeId = $this->randomName();

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

    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('editor_default_settings', array($this->editorId))
      ->will($this->returnValue(array()));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('editor_default_settings', array(), $this->editorId)
      ->will($this->returnValue(array()));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.editor', $this->editorPluginManager);
    $container->set('module_handler', $this->moduleHandler);
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
    $this->assertContains('filter.format.test', $dependencies['entity']);
  }

}
