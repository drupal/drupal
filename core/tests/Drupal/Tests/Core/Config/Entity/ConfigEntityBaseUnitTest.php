<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityBase
 *
 * @group Drupal
 * @group Config
 */
class ConfigEntityBaseUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

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
   * The provider of the entity type.
   *
   * @var string
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Config\Entity\ConfigEntityBase unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $values = array();
    $this->entityTypeId = $this->randomName();
    $this->provider = $this->randomName();
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue($this->provider));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);

    $this->entity = $this->getMockForAbstractClass('\Drupal\Core\Config\Entity\ConfigEntityBase', array($values, $this->entityTypeId));
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Calculating dependencies will reset the dependencies array.
    $this->entity->set('dependencies', array('module' => array('node')));
    $this->assertEmpty($this->entity->calculateDependencies());
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveDuringSync() {
    $query = $this->getMock('\Drupal\Core\Entity\Query\QueryInterface');
    $storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigStorageControllerInterface');

    $query->expects($this->any())
      ->method('execute')
      ->will($this->returnValue(array()));
    $query->expects($this->any())
      ->method('condition')
      ->will($this->returnValue($query));
    $storage->expects($this->any())
      ->method('getQuery')
      ->will($this->returnValue($query));
    $storage->expects($this->any())
      ->method('loadUnchanged')
      ->will($this->returnValue($this->entity));

    // Saving an entity will not reset the dependencies array during config
    // synchronization.
    $this->entity->set('dependencies', array('module' => array('node')));
    $this->entity->preSave($storage);
    $this->assertEmpty($this->entity->get('dependencies'));

    $this->entity->setSyncing(TRUE);
    $this->entity->set('dependencies', array('module' => array('node')));
    $this->entity->preSave($storage);
    $dependencies = $this->entity->get('dependencies');
    $this->assertContains('node', $dependencies['module']);
  }

  /**
   * @covers ::addDependency
   */
  public function testAddDependency() {
    $method = new \ReflectionMethod('\Drupal\Core\Config\Entity\ConfigEntityBase', 'addDependency');
    $method->setAccessible(TRUE);
    $method->invoke($this->entity, 'module', $this->provider);
    $method->invoke($this->entity, 'module', 'Core');
    $method->invoke($this->entity, 'module', 'node');
    $dependencies = $this->entity->get('dependencies');
    $this->assertNotContains($this->provider, $dependencies['module']);
    $this->assertNotContains('Core', $dependencies['module']);
    $this->assertContains('node', $dependencies['module']);

    // Test sorting of dependencies.
    $method->invoke($this->entity, 'module', 'action');
    $dependencies = $this->entity->get('dependencies');
    $this->assertEquals(array('action', 'node'), $dependencies['module']);

    // Test sorting of dependency types.
    $method->invoke($this->entity, 'entity', 'system.action.id');
    $dependencies = $this->entity->get('dependencies');
    $this->assertEquals(array('entity', 'module'), array_keys($dependencies));
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithPluginBag() {
    $values = array();
    $this->entity = $this->getMockBuilder('\Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginBag')
      ->setConstructorArgs(array($values, $this->entityTypeId))
      ->setMethods(array('getPluginBag'))
      ->getMock();

    // Create a configurable plugin that would add a dependency.
    $instance_id = $this->randomName();
    $instance = new TestConfigurablePlugin(array(), $instance_id, array('provider' => 'test'));

    // Create a plugin bag to contain the instance.
    $pluginBag = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultPluginBag')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();
    $pluginBag->expects($this->atLeastOnce())
      ->method('get')
      ->with($instance_id)
      ->will($this->returnValue($instance));
    $pluginBag->addInstanceId($instance_id);

    // Return the mocked plugin bag.
    $this->entity->expects($this->once())
                 ->method('getPluginBag')
                 ->will($this->returnValue($pluginBag));

    $dependencies = $this->entity->calculateDependencies();
    $this->assertContains('test', $dependencies['module']);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithPluginBagSameProviderAsEntityType() {
    $values = array();
    $this->entity = $this->getMockBuilder('\Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginBag')
                         ->setConstructorArgs(array($values, $this->entityTypeId))
                         ->setMethods(array('getPluginBag'))
                         ->getMock();

    // Create a configurable plugin that will not add a dependency since it is
    // provider matches the provider of the entity type.
    $instance_id = $this->randomName();
    $instance = new TestConfigurablePlugin(array(), $instance_id, array('provider' => $this->provider));

    // Create a plugin bag to contain the instance.
    $pluginBag = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultPluginBag')
                      ->disableOriginalConstructor()
                      ->setMethods(array('get'))
                      ->getMock();
    $pluginBag->expects($this->atLeastOnce())
              ->method('get')
              ->with($instance_id)
              ->will($this->returnValue($instance));
    $pluginBag->addInstanceId($instance_id);

    // Return the mocked plugin bag.
    $this->entity->expects($this->once())
                 ->method('getPluginBag')
                 ->will($this->returnValue($pluginBag));

    $this->assertEmpty($this->entity->calculateDependencies());
  }

}

class TestConfigurablePlugin extends PluginBase implements ConfigurablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

}
